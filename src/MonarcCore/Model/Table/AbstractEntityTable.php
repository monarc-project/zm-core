<?php
namespace MonarcCore\Model\Table;

abstract class AbstractEntityTable
{
    protected $db;
    protected $class;
    protected $language;
    protected $connectedUser;

    public function __construct(\MonarcCore\Model\Db $dbService, $class = null)
    {
        $this->db = $dbService;
        if ($class != null) {
            $this->class = $class;
        } else {
            $thisClassName = get_class($this);
            $classParts = explode('\\', $thisClassName);
            $lastClassPart = end($classParts);
            $this->class = '\MonarcCore\Model\Entity\\' . substr($lastClassPart, 0, -5);
        }
    }
    public function getDb()
    {
        if(!$this->db) {
            $this->db = $this->getServiceLocator()->get('MonarcCore\Model\Db');
        }
        return $this->db;
    }

    public function getRepository()
    {
        return $this->getDb()->getRepository($this->getClass());
    }

    public function getClass(){
        return $this->class;
    }

    public function setConnectedUser($cu){
        $this->connectedUser = $cu;
        return $this;
    }
    public function getConnectedUser(){
        return $this->connectedUser;
    }

    public function fetchAll($fields = array())
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            $all = $this->getDb()->fetchAll(new $c());
            $return = array();
            foreach ($all as $a) {
                $return[] = $a->getJsonArray($fields);
            }
            return $return;
        } else {
            return false;
        }
    }

    public function fetchAllFiltered($fields = array(), $page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null)
    {
        $class = $this->getClass();

        if (class_exists($class)) {
            $all = $this->getDb()->fetchAllFiltered(new $class(), $page, $limit, $order, $filter, $filterAnd, $filterJoin, $filterLeft);
            $return = array();
            foreach ($all as $a) {
                $return[] = $a->getJsonArray($fields);
            }
            return $return;
        } else {
            return false;
        }
    }

    public function count()
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            return $this->getDb()->count(new $c());
        } else {
            return false;
        }
    }

    public function countFiltered($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null)
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            return $this->getDb()->countFiltered(new $c(), $limit, $order, $filter, $filterAnd, $filterJoin, $filterLeft);
        } else {
            return false;
        }
    }

    public function get($id, $fields = array())
    {
        $ent = $this->getEntity($id);
        if ($ent !== false) {
            return $ent->getJsonArray($fields);
        } else {
            return false;
        }
    }

    public function getEntity($id)
    {
        $class = $this->getClass();
        if(class_exists($class)){
            $entity = new $class();
            $entity->setDbAdapter($this->getDb());
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);
            return $entity;
        }else{
            return false;
        }
    }

    public function getEntityByFields($fields = array(), $orderBy = array()) {
        $class = $this->getClass();
        if (class_exists($class)) {
            $entity = new $class();
            $entity->setDbAdapter($this->getDb());

            return $this->getDb()->fetchByFields($entity, $fields, $orderBy);
        } else {
            return false;
        }
    }

    public function save(\MonarcCore\Model\Entity\AbstractEntity $entity)
    {
        if(!empty($this->connectedUser) && isset($this->connectedUser['firstname']) && isset($this->connectedUser['lastname'])){
            $id = $entity->get('id');
            if(empty($id)){
                $entity->set('creator',trim($this->connectedUser['firstname']." ".$this->connectedUser['lastname']));
                $entity->set('createdAt',new \DateTime());
            }else{
                $entity->set('updater',trim($this->connectedUser['firstname']." ".$this->connectedUser['lastname']));
                $entity->set('updatedAt',new \DateTime());
            }
        }

        $id = $this->getDb()->save($entity);

        return $id;
    }

    public function delete($id)
    {
        $c = $this->getClass();
        if(class_exists($c)){
            $id  = (int) $id;

            $entity = new $c();
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);

            $this->getDb()->delete($entity);
            return true;
        }else{
            return false;
        }
    }

    public function deleteList($data){
        $c = $this->getClass();
        if(class_exists($c) && is_array($data)){
            $entity = new $c();
            $entities = $this->getDb()->fetchByIds($entity,$data);
            if(!empty($entities)){
                $this->getDb()->deleteAll($entities);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * Change positions by parent
     *
     * @param $field
     * @param $parentId
     * @param $position
     * @param string $direction
     * @param string $referential
     * @param bool $strict
     * @return array
     */
    public function changePositionsByParent($field = 'parent', $parentId, $position, $direction = 'up', $referential = 'after', $strict = false)
    {
        $positionDirection = ($direction == 'up') ? '+1' : '-1';
        $sign = ($referential == 'after') ? '>' : '<';
        if (!$strict) {
            $sign .= '=';
        }

        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position' . $positionDirection);
        if(empty($parentId)){
            $return = $return->where('t.' . $field . ' IS NULL');
        }else{
            $return = $return->where('t.' . $field . ' = :parentid')
            ->setParameter(':parentid', $parentId);
            
        }
        $return = $return->andWhere('t.position ' . $sign . ' :position')
            ->setParameter(':position', $position)
            ->getQuery()
            ->getResult();
        return $return;
    }

    /**
     * Max Position By Parent
     *
     * @param $field
     * @param $parentId
     * @return mixed
     */
    public function maxPositionByParent($field, $parentId)
    {
        $maxPosition = $this->getRepository()->createQueryBuilder('t')
            ->select(array('max(t.position)'));
        if(empty($parentId)){
            $maxPosition = $maxPosition->where('t.' . $field . ' IS NULL');
        }else{
            $maxPosition = $maxPosition->where('t.' . $field . ' = :parentid')
            ->setParameter(':parentid', $parentId);
        }
        $maxPosition = $maxPosition->getQuery()
            ->getResult();

        return $maxPosition[0][1];
    }

    public function getReference($id){
        return $this->getDb()->getReference($this->getClass(),$id);
    }
}
