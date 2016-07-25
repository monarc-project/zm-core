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

    const IS_GENERIC = 0;
    const IS_SPECIFIC = 1;

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

        // Ensure that we're not trying to add a specific item if the father is generic
        $father = $this->objectTable->get($data['father']);
        $child = $this->objectTable->get($data['child']);

        if ($father['mode'] == self::IS_GENERIC && $child['mode'] == self::IS_SPECIFIC) {
            throw new \Exception("You cannot add a specific object to a generic parent", 412);
        }

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

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;
        $position = $this->managePositionCreation('father', $data['father'], (int) $data['implicitPosition'], $previous);
        $entity->setPosition($position);

        return $this->get('table')->save($entity);
    }

<<<<<<< HEAD
    /**
     * Get Childs
     *
     * @param $objectId
     * @return mixed
     */
    public function getChilds($objectId) {
        return $this->get('table')->getChilds($objectId);
=======
    public function getRecursiveChildren($father_id) {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $children = $table->getEntityByFields(array('father' => $father_id), array('position' => 'ASC'));
        $array_children = [];

        foreach ($children as $child) {
            /** @var ObjectObject $child */
            $child_array = $child->getJsonArray();
            $child_array['children'] = $this->getRecursiveChildren($child_array['child']);

            $object_child = $this->get('objectTable')->get($child_array['child']);
            $object_child['component_link_id'] = $child_array['id'];
            $array_children[] = $object_child;
        }

        return $array_children;
    }

    public function moveObject($id, $direction) {
        $entity = $this->get('table')->getEntity($id);

        if ($entity->position == 1 && $direction == 'up') {
            // Nothing to do
            return;
        }

        $this->manageRelativePositionUpdate('father', $entity, $direction);
>>>>>>> 9d74ad411967be33e057512b62ef0bbefc3c8660
    }
}