<?php
namespace MonarcCore\Model\Table;

abstract class AbstractEntityTable
{
    protected $db;
    protected $class;

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
    protected function getDb()
    {
        if(!$this->db) {
            $this->db = $this->getServiceLocator()->get('MonarcCore\Model\Db');
        }
        return $this->db;
    }

    public function getClass(){
        return $this->class;
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

    public function fetchAllFiltered($fields = array(), $page = 1, $limit = 25, $order = null, $filter = null)
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            $all = $this->getDb()->fetchAllFiltered(new $c(), $page, $limit, $order, $filter);
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

    public function countFiltered($page = 1, $limit = 25, $order = null, $filter = null)
    {
        $c = $this->getClass();
        if (class_exists($c)) {
            return $this->getDb()->countFiltered(new $c(), $page, $limit, $order, $filter);
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
        $c = $this->getClass();
        if(class_exists($c)){
            //$id  = (int)$id;
            $entity = new $c();
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);
            return $entity;
        }else{
            return false;
        }
    }

    public function save(\MonarcCore\Model\Entity\AbstractEntity $entity)
    {
        $id = (int)$entity->get('id');
        $this->getDb()->save($entity);
        if ($id == 0) {
            //$id = $this->tableGateway->getLastInsertValue();
        }
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
}
