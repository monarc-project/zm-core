<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\MonarcObject;
use MonarcCore\Model\Entity\ObjectObject;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ObjectObjectTable;
use Zend\EventManager\EventManager;

/**
 * Object Object Service
 *
 * Class ObjectObjectService
 * @package MonarcCore\Service
 */
class ObjectObjectService extends AbstractService
{
    protected $anrTable;
    protected $userAnrTable;
    protected $objectTable;
    protected $instanceTable;
    protected $childTable;
    protected $fatherTable;
    protected $modelTable;
    protected $dependencies = ['[child](object)', '[father](object)', '[anr](object)'];

    /**
     * @inheritdoc
     */
    public function create($data, $last = true, $context = MonarcObject::BACK_OFFICE)
    {
        if ($data['father'] == $data['child']) {
            throw new \MonarcCore\Exception\Exception("You cannot add yourself as a component", 412);
        }

        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('table');

        //verify child not already existing
        $objectsObjects = $objectObjectTable->getEntityByFields(['anr' => (empty($data['anr']) ? null : $data['anr']), 'father' => $data['father'], 'child' => $data['child']]);
        if (count($objectsObjects)) {
            throw new \MonarcCore\Exception\Exception('This component already exist for this object', 412);
        }

        $recursiveParentsListId = [];
        $this->getRecursiveParentsListId($data['father'], $recursiveParentsListId);

        if (isset($recursiveParentsListId[$data['child']])) {
            throw new \MonarcCore\Exception\Exception("You cannot create a cyclic dependency", 412);
        }

        /** @var ObjectTable $objectTable */
        $objectTable = $this->get('objectTable');

        // Ensure that we're not trying to add a specific item if the father is generic
        $father = $objectTable->getEntity($data['father']);
        $child = $objectTable->getEntity($data['child']);

        // on doit déterminer si par voie de conséquence, cet objet ne va pas se retrouver dans un modèle dans lequel il n'a pas le droit d'être
        if ($context == MonarcObject::BACK_OFFICE) {
            $models = $father->get('asset')->get('models');
            foreach ($models as $m) {
                $this->get('modelTable')->canAcceptObject($m->get('id'), $child, $context);
            }
        }

        if ($father->mode == ObjectMonarcObject::MODE_GENERIC && $child->mode == ObjectMonarcObject::MODE_SPECIFIC) {
            throw new \MonarcCore\Exception\Exception("You cannot add a specific object to a generic parent", 412);
        }

        if (!empty($data['implicitPosition'])) {
            unset($data['position']);
        } else if (!empty($data['position'])) {
            unset($data['implicitPosition']);
        }

        /** @var ObjectObject $entity */
        $entity = $this->get('entity');

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->exchangeArray($data);

        $this->setDependencies($entity, $this->dependencies);

        $id = $objectObjectTable->save($entity);

        //link to anr
        $parentAnrs = [];
        $childAnrs = [];
        if ($father->anrs) {
            foreach ($father->anrs as $anr) {
                $parentAnrs[] = $anr->id;
            }
        }
        if ($child->anrs) {
            foreach ($child->anrs as $anr) {
                $childAnrs[] = $anr->id;
            }
        }

        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        foreach ($parentAnrs as $anrId) {
            if (!in_array($anrId, $childAnrs)) {
                $child->addAnr($anrTable->getEntity($anrId));
            }
        }

        $objectTable->save($child);

        //create instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instancesParent = $instanceTable->getEntityByFields(['object' => $father->id]);

        foreach ($instancesParent as $instanceParent) {
            $anrId = $instanceParent->anr->id;

            $previousInstance = false;
            if ($data['implicitPosition'] == 3) {
                $previousObject = $objectObjectTable->get($data['previous'])['child'];
                $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $previousObject->id]);
                foreach ($instances as $instance) {
                    $previousInstance = $instance->id;
                }
            }

            $dataInstance = [
                'object' => $child->id,
                'parent' => $instanceParent->id,
                'root' => ($instanceParent->root) ? $instanceParent->root->id : $instanceParent->id,
                'implicitPosition' => $data['implicitPosition'],
                'c' => -1,
                'i' => -1,
                'd' => -1,
            ];

            if ($previousInstance) {
                $dataInstance['previous'] = $previousInstance;
            }

            //if father instance exist, create instance for child
            $eventManager = new EventManager();
            $eventManager->setIdentifiers('addcomponent');

            $sharedEventManager = $eventManager->getSharedManager();
            $eventManager->setSharedManager($sharedEventManager);
            $eventManager->trigger('createinstance', null, compact(['anrId', 'dataInstance']));
        }

        return $id;
    }

    /**
     * Fetch and returns the children of the object
     * @param int $objectId The object ID
     * @return array The children objects
     */
    public function getChildren($objectId)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        return $table->getEntityByFields(['father' => $objectId], ['position' => 'DESC']);
    }

    /**
     * Recursively fetches and return the children
     * @param int $fatherId The parent object ID
     * @param int $anrId The ANR ID
     * @return array The children
     */
    public function getRecursiveChildren($fatherId, $anrId = null)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $filters = ['father' => $fatherId];

        if (!is_null($anrId)) {
            $filters['anr'] = $anrId;
        }

        $children = $table->getEntityByFields($filters, ['position' => 'ASC']);
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

    /**
     * Recursively fetches and returns the parent objects
     * @param int $parent_id The parent object ID
     * @return array The parents
     */
    public function getRecursiveParents($parent_id)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $parents = $table->getEntityByFields(['child' => $parent_id], ['position' => 'ASC']);
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

    /**
     * Fetches and returns a list of parents recursively
     * @param int $parentId The parent ID
     * @param array $array A reference to the array that will contain the parents
     */
    public function getRecursiveParentsListId($parentId, &$array)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $parents = $table->getEntityByFields(['child' => $parentId], ['position' => 'ASC']);
        $array[$parentId] = $parentId;

        foreach ($parents as $parent) {
            $this->getRecursiveParentsListId($parent->father->id, $array);
        }
    }

    /**
     * Moves an object's position
     * @param int $id The object ID to move
     * @param string $direction The direction to move the object towards, either 'up' or 'down'
     */
    public function moveObject($id, $direction)
    {
        $entity = $this->get('table')->getEntity($id);

        if ($entity->position == 1 && $direction == 'up') {
            // Nothing to do
            return;
        }

        $this->manageRelativePositionUpdate('father', $entity, $direction);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');
        $objectObject = $table->getEntity($id);

        if (!$objectObject) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
        }

        //delete instance instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $childInstances = $instanceTable->getEntityByFields(['object' => $objectObject->child->id]);
        $fatherInstances = $instanceTable->getEntityByFields(['object' => $objectObject->father->id]);

        foreach ($childInstances as $childInstance) {
            foreach ($fatherInstances as $fatherInstance) {
                if ($childInstance->parent && $childInstance->parent->id == $fatherInstance->id) {
                    $childInstance->parent = null;
                    $childInstance->root = null;
                    $instanceTable->delete($childInstance->id);
                }
            }
        }

        parent::delete($id);
    }
}
