<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\ObjectObject;
use MonarcCore\Model\Table\ObjectObjectTable;

/**
 * Object Object Service
 *
 * Class ObjectObjectService
 * @package MonarcCore\Service
 */
class ObjectObjectService extends AbstractService
{
    protected $objectTable;
    protected $dependencies = ['child'];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {
        if ($data['father'] == $data['child']) {
            throw new \Exception("You cannot add yourself as a component", 412);
        }

        /** @var ObjectTable $objectTable */
        $objectTable = $this->objectTable;

        // Ensure that we're not trying to add a specific item if the father is generic
        $father = $objectTable->get($data['father']);
        $child = $objectTable->get($data['child']);

        if ($father['mode'] == ObjectObject::IS_GENERIC && $child['mode'] == ObjectObject::IS_SPECIFIC) {
            throw new \Exception("You cannot add a specific object to a generic parent", 412);
        }

        /** @var ObjectObject $entity */
        $class = $this->get('entity');
        $entity = new $class();
        $entity->exchangeArray($data);

        $fatherValue = $entity->get('father');
        if (!empty($fatherValue)) {
            $fatherEntity = $objectTable->getEntity($fatherValue);
            $entity->setFather($fatherEntity);
        }

        $childValue = $entity->get('child');
        if (!empty($childValue)) {
            $childEntity = $objectTable->getEntity($childValue);
            $entity->setChild($childEntity);
        }

        if (array_key_exists('implicitPosition', $data)) {
            $previous = (isset($data['previous'])) ? $data['previous'] : null;
            $position = $this->managePositionCreation('father', $data['father'], (int) $data['implicitPosition'], $previous);
            $entity->setPosition($position);
        } else if (array_key_exists('position', $data)) {
            $entity->setPosition((int) $data['position']);
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Get Childs
     *
     * @param $objectId
     * @return mixed
     */
    public function getChildren($objectId)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        return $table->getEntityByFields(array('father' => $objectId), array('position' => 'ASC'));
    }

    public function getRecursiveChildren($father_id) {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $children = $table->getEntityByFields(array('father' => $father_id), array('position' => 'ASC'));
        $array_children = [];

        foreach ($children as $child) {
            /** @var ObjectObject $child */
            $child_array = $child->getJsonArray();

            $object_child = $this->get('objectTable')->get($child_array['child']);
            $object_child['children'] = $this->getRecursiveChildren($child_array['child']);
            $object_child['component_link_id'] = $child_array['id'];
            $array_children[] = $object_child;
        }

        return $array_children;
    }

    public function getRecursiveParents($parent_id){
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $parents = $table->getEntityByFields(array('child' => $parent_id), array('position' => 'ASC'));
        $array_parents = [];

        foreach ($parents as $parent) {
            /** @var ObjectObject $parent */
            $parent_array = $parent->getJsonArray();

            $object_parent = $this->get('objectTable')->get($parent_array['father']);
            $object_parent['parents'] = $this->getRecursiveParents($parent_array['father']);
            $object_parent['component_link_id'] = $parent_array['id'];
            $array_parents[] = $object_parent;
        }

        return $array_parents;
    }

    public function moveObject($id, $direction) {
        $entity = $this->get('table')->getEntity($id);

        if ($entity->position == 1 && $direction == 'up') {
            // Nothing to do
            return;
        }

        $this->manageRelativePositionUpdate('father', $entity, $direction);
    }
}