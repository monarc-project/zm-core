<?php
namespace MonarcCore\Service;

/**
 * Object Object Service
 *
 * Class ObjectObjectService
 * @package MonarcCore\Service
 */
class ObjectObjectService extends AbstractService
{
    protected $objectTable;

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $class = $this->get('entity');
        $entity = new $class();

        $entity->exchangeArray($data);

        $fatherValue = $entity->get('father');
        if (!empty($fatherValue)) {
            $fatherEntity = $this->get('objectTable')->getEntity($fatherValue);
            $entity->setFather($fatherEntity);
        }

        $childValue = $entity->get('child');
        if (!empty($childValue)) {
            $childEntity = $this->get('objectTable')->getEntity($childValue);
            $entity->setChild($childEntity);
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Get Childs
     *
     * @param $objectId
     * @return mixed
     */
    public function getChilds($objectId) {
        return $this->get('table')->getChilds($objectId);
    }
}