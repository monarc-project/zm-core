<?php
namespace MonarcCore\Model\Table;

abstract class AbstractEntityTable
{
    protected $db;
    protected $class;

    public function __construct(\MonarcCore\Model\Db $dbService)
    {
        $this->db = $dbService;
        $this->class = '\MonarcCore\Model\Entity\\'.substr(end(explode('\\', get_class($this))), 0, -5);
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

    public function fetchAll() // TODO: ajouter pagination
    {
        $c = $this->getClass();
        if(class_exists($c)){
            $all = $this->getDb()->fetchAll(new $c());
            $return = array();
            foreach($all as $a){
                $return[] = $a->getJsonArray();
            }
            return $return;
        }else{
            return false;
        }
    }

    public function get($id)
    {
        $c = $this->getClass();
        if(class_exists($c)){
            //$id  = (int)$id;
            $entity = new $c();
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);
            if(!empty($entity)){
                return $entity->getJsonArray();
            }else{
                return array();
            }
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
            $this->getDb()->delete($entity);
            return true;
        }else{
            return false;
        }
    }
}
