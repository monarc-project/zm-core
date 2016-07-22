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

        $entity = $this->get('entity');
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
}