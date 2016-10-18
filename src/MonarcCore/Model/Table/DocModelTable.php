<?php
namespace MonarcCore\Model\Table;

class DocModelTable extends AbstractEntityTable {

	public function delete($id, $last = true)
    {
        $c = $this->getClass();
        if(class_exists($c)){
            $id  = (int) $id;

            $entity = new $c();
            $entity->set('id',$id);
            $entity = $this->getDb()->fetch($entity);

            if(file_exists($entity->get('path'))){
            	unlink($entity->get('path'));
            }

            $this->getDb()->delete($entity, $last);
            return true;
        }else{
            return false;
        }
    }
}