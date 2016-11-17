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

    public function getClassMetadata(){
        return $this->getDb()->getClassMetadata($this->getClass());
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
    public function fetchAllObject(){
        $c = $this->getClass();
        if (class_exists($c)) {
            return $this->getDb()->fetchAll(new $c());
        }else{
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

            if (!$entity) {
                throw new \Exception('Entity does not exist', 412);
            }
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

    public function save(\MonarcCore\Model\Entity\AbstractEntity &$entity, $last = true)
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
        $params = $entity->get('parameters');
        if(!empty($params['implicitPosition']['value'])){
            $this->manageSavePosition($entity,$params['implicitPosition']);

            // Unset cache data, if new save on object
            unset($params['implicitPosition']['value']);
            unset($params['implicitPosition']['previous']);
            unset($params['implicitPosition']['oldField']);
            unset($params['implicitPosition']['oldPosition']);
            unset($params['implicitPosition']['newField']);
            unset($params['implicitPosition']['newPosition']);
            $entity->set('parameters',$params);

            $this->updatePositionReferences($entity,$params['implicitPosition']);
        }

        $id = $this->getDb()->save($entity, $last);

        return $id;
    }

    protected function updatePositionReferences(\MonarcCore\Model\Entity\AbstractEntity &$entity,$params = array()){
        if(!empty($params['field'])){
            $v = $entity->get($params['field']);
            if(!empty($v) && !is_object($v)){
                try{
                    $class = $this->getClassMetadata()->getAssociationTargetClass($params['field']);
                    if(!empty($class)){
                        $dep = $this->getDb()->getReference($class,$entity->get($params['field']));
                        $entity->set($params['field'],$dep);
                    }
                }catch(\Doctrine\Common\Proxy\Exception\InvalidArgumentException $e){
                }
            }
        }
        if(!empty($params['root'])){
            $v = $entity->get($params['root']);
            if(!empty($v) && !is_object($v)){
                try{
                    $class = $this->getClassMetadata()->getAssociationTargetClass($params['root']);
                    if(!empty($class)){
                        $dep = $this->getDb()->getReference($class,$entity->get($params['root']));
                        $entity->set($params['root'],$dep);
                    }
                }catch(\Doctrine\Common\Proxy\Exception\InvalidArgumentException $e){
                }
            }
        }
    }

    protected function manageSavePosition(\MonarcCore\Model\Entity\AbstractEntity &$entity,$params = array()){
        $id = $entity->get('id');
        if(empty($id)){ // create
            switch ($params['value']) {
                case 1: // start
                    $params['newPosition'] = 1;
                case 3: // after element
                    $entity->set('position',$params['newPosition']);
                    $return = $this->getRepository()->createQueryBuilder('t')
                        ->update()
                        ->set('t.position', 't.position + 1');
                    
                    $return = $this->buildWhereForPositionCreate($params,$return,$entity);
                    $return->getQuery()->getResult();
                    break;
                case 2: // end
                default:
                    $entity->set('position',$this->countPositionMax($entity,$params));
                    break;
            }
            $this->updateEntityRoot($entity,$params);
        }else{ // update
            $changeParents = false;
            if(!empty($params['field'])){
                if(isset($params['oldField'][$params['field']]) && isset($params['newField'][$params['field']]) && $params['oldField'][$params['field']] != $params['newField'][$params['field']]){
                    $changeParents = true;
                }
            }

            if(!$changeParents && $params['oldPosition'] != $params['newPosition'] && $params['value'] != 1 && $params['value'] != 3){ // start & after
                // pas de changement de parents & end
                $params['newPosition'] = $this->countPositionMax($entity,$params);
            }
            if($changeParents || $params['oldPosition'] != $params['newPosition']){
                // update old brothers position
                $return = $this->getRepository()->createQueryBuilder('t')
                    ->update()
                    ->set('t.position', 't.position - 1');
                
                $return = $this->buildWhereForPositionCreate($params,$return,$entity,'old');
                $return->getQuery()->getResult();
                // update new brothers position
                switch ($params['value']) {
                    case 1: // start
                        $params['newPosition'] = 1;
                    case 3: // after element
                        $entity->set('position',$params['newPosition']);
                        $return = $this->getRepository()->createQueryBuilder('t')
                            ->update()
                            ->set('t.position', 't.position + 1');
                        
                        $return = $this->buildWhereForPositionCreate($params,$return,$entity);
                        $return->getQuery()->getResult();
                        break;
                    case 2: // end
                    default:
                        $entity->set('position',$params['newPosition']); // pas de +1 car on a déjà le max d'élément
                        break;
                }
                $this->updateEntityRoot($entity,$params);
            }
        }
        return $entity;
    }

    protected function countPositionMax(\MonarcCore\Model\Entity\AbstractEntity $entity,$params = array()){
        $return = $this->getRepository()->createQueryBuilder('t')
            ->select('COUNT(t.id)');
        if(!empty($params['field'])){
            if(isset($params['newField'][$params['field']])){
                if(is_null($params['newField'][$params['field']])){
                    $return = $return->where('t.'.$params['field'].' IS NULL');
                }else{
                    $return = $return->where('t.' . $params['field'] . ' = :'.$params['field'])
                        ->setParameter(':'.$params['field'], $params['newField'][$params['field']]);
                }
            }
        }
        $id = $entity->get('id');
        return $return->getQuery()->getSingleScalarResult()+($id?0:1);
    }

    protected function updateEntityRoot(\MonarcCore\Model\Entity\AbstractEntity &$entity,$params = array(), $root = null){
        if(!empty($params['root'])){
            if(!empty($params['field'])){
                $initRoot = $entity->get($params['root']);
                if(empty($root)){
                    $po = $entity->get($params['field']);
                    $entity->set($params['root'],null);
                    if(!empty($po)){
                        $parent = $this->getEntity(is_object($po)?$po->get('id'):$po);
                        if($parent){
                            $o = $parent->get($params['root']);
                            $ro = empty($o)?$parent->get('id'):(is_object($o)?$o->get('id'):$o);
                            $entity->set($params['root'],$ro);
                        }
                    }
                }else{
                    $entity->set($params['root'],$root);
                }
                if($initRoot != $entity->get($params['root']) && !empty($params['field']) && $entity->get('id')){
                    $entities = $this->getEntityByFields([$params['field']=>$entity->get('id')]);
                    if(empty($root)){
                        $nr = $entity->get($params['root']);
                        $nr = empty($nr)?$entity->get('id'):$nr;
                    }else{
                        $nr = $root;
                    }
                    foreach($entities as $ent){
                        $this->updateEntityRoot($ent,$params,$nr);
                        $this->save($ent,false);
                    }
                }
            }
        }
    }

    protected function buildWhereForPositionCreate($params,$queryBuilder,\MonarcCore\Model\Entity\AbstractEntity $entity, $newOrOld = 'new'){
        $hasWhere = false;
        if(!empty($params['field'])){
            if(isset($params[$newOrOld.'Field'][$params['field']])){
                $hasWhere = true;
                if(is_null($params[$newOrOld.'Field'][$params['field']])){
                    $queryBuilder = $queryBuilder->where('t.'.$params['field'].' IS NULL');
                }else{
                    $queryBuilder = $queryBuilder->where('t.' . $params['field'] . ' = :'.$params['field'])
                        ->setParameter(':'.$params['field'], $params[$newOrOld.'Field'][$params['field']]);
                }
            }
        }
        if(!empty($params[$newOrOld.'Position'])){
            if($hasWhere){
                $queryBuilder = $queryBuilder->andWhere('t.position >'.($newOrOld=='new'?'=':'').' :pos');
            }else{
                $queryBuilder = $queryBuilder->where('t.position >'.($newOrOld=='new'?'=':'').' :pos');
            }
            $queryBuilder = $queryBuilder->setParameter(':pos', $params[$newOrOld.'Position']);
            $hasWhere = true;
        }
        $id = $entity->get('id');
        if($id){
            if($hasWhere){
                $queryBuilder = $queryBuilder->andWhere('t.id != :id');
            }else{
                $queryBuilder = $queryBuilder->where('t.id != :id');
            }
            $queryBuilder = $queryBuilder->setParameter(':id', $id);
        }
        return $queryBuilder;
    }

    public function delete($id, $last = true)
    {
        $c = $this->getClass();
        if(class_exists($c)){
            $id  = (int) $id;

            $entity = new $c();
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);

            $params = $entity->get('parameters');
            if(!empty($params['implicitPosition'])){
                $this->manageDeletePosition($entity,$params['implicitPosition']);
            }

            $this->getDb()->delete($entity, $last);
            return true;
        }else{
            return false;
        }
    }

    protected function manageDeletePosition(\MonarcCore\Model\Entity\AbstractEntity $entity,$params = array()){
        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position - 1');
        $hasWhere = false;
        if(!empty($params['field'])){
            $hasWhere = true;
            if(is_null($entity->get($params['field']))){
                $return = $return->where('t.'.$params['field'].' IS NULL');
            }else{
                $return = $return->where('t.' . $params['field'] . ' = :'.$params['field'])
                    ->setParameter(':'.$params['field'], $entity->get($params['field']));
            }
        }
        if($hasWhere){
            $return = $return->andWhere('t.position >= :pos');
        }else{
            $return = $return->where('t.position >= :pos');
        }
        $return = $return->setParameter(':pos', $entity->get('position'));
        $return->getQuery()->getResult();
    }

    public function deleteList($data){
        $c = $this->getClass();
        if(class_exists($c) && is_array($data)){
            $entity = new $c();
            $entities = $this->getDb()->fetchByIds($entity,$data);
            if(!empty($entities)){
                $params = $entity->get('parameters');
                if(!empty($params['implicitPosition'])){
                    // C'est un peu bourrin
                    foreach($entities as $e){
                        $this->manageDeletePosition($e,$params['implicitPosition']);
                    }
                }
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
            ->getQuery();

        $return->getResult();
        return $return;
    }

    /**
     * Change positions
     *
     * @param $position
     * @param string $direction
     * @param string $referential
     * @param bool $strict
     * @return \Doctrine\ORM\Query
     */
    public function changePositions($position, $direction = 'up', $referential = 'after', $strict = false)
    {
        $positionDirection = ($direction == 'up') ? '+1' : '-1';
        $sign = ($referential == 'after') ? '>' : '<';
        if (!$strict) {
            $sign .= '=';
        }

        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position' . $positionDirection)
            ->andWhere('t.position ' . $sign . ' :position')
            ->setParameter(':position', $position)
            ->getQuery();

        $return->getResult();
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



    /**
     * Get Descendants
     * @param $id
     * @return array
     */
    public function getDescendants($id) {

        $childList = [];

        $this->getRecursiveChild($childList, $id);

        return $childList;
    }

    /**
     * Get Recursive Child
     *
     * @param $childList
     * @param $id
     */
    protected function getRecursiveChild(&$childList, $id) {
        $children = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        if (count($children)) {
            foreach ($children as $child) {
                $childList[] = $child['id'];
                $this->getRecursiveChild($childList, $child['id']);
            }
        }
    }


    public function getDescendantsObjects($id) {

        $childList = [];

        $this->getRecursiveChildObjects($childList, $id);

        return $childList;
    }

    protected function getRecursiveChildObjects(&$childList, $id) {

        $children = $this->getRepository()->createQueryBuilder('t')
            ->select()
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        foreach ($children as $child) {
            $childList[] = $child;
            $this->getRecursiveChildObjects($childList, $child->id);
        }

    }
}
