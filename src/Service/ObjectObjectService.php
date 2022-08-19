<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectObjectSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Table\MonarcObjectTable;
use Monarc\Core\Table\ObjectObjectTable;
use Laminas\EventManager\EventManager;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Laminas\EventManager\SharedEventManager;
use function in_array;
use function is_object;

class ObjectObjectService
{
    protected $anrTable;
    protected $userAnrTable;
    protected $MonarcObjectTable;
    protected $instanceTable;
    // TODO: we don't need the deps
    protected $childTable;
    protected $parentTable;
    protected $dependencies = ['[child](object)', '[parent](object)', '[anr](object)'];

    // TODO: inject the EventManager or replace it's usage to directly call/execute the functionality.
    /** @var SharedEventManager */
    private $sharedManager;
//    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
//    {
//        $objectObjectService = parent::__invoke($container, $requestedName, $options);
//
//        $objectObjectService->setSharedManager($container->get('EventManager')->getSharedManager());
//
//        return $objectObjectService;
//    }

    private ObjectObjectTable $objectObjectTable;

    public function __construct(ObjectObjectTable $objectObjectTable)
    {
        $this->objectObjectTable = $objectObjectTable;
    }

    public function create($data, $last = true, $context = MonarcObject::BACK_OFFICE)
    {
        if ($data['parent'] === $data['child']) {
            throw new Exception('You cannot add yourself as a component', 412);
        }

        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('table');

        //verify child not already existing
        if (isset($data['parent']['anr'], $data['parent']['uuid'], $data['child']['anr'], $data['child']['uuid'])) {
            $objectsObjects = $objectObjectTable->getEntityByFields([
                'anr' => empty($data['anr']) ? null : $data['anr'],
                'father' => $data['parent'],
                'child' => $data['child']
            ]);
        } else {
            $queryParams = [
                'parent' => $data['parent'],
                'child' => $data['child'],
            ];
            if (!empty($data['anr'])) {
                $queryParams['anr'] = $data['anr'];
            }
            $objectsObjects = $objectObjectTable->getEntityByFields($queryParams);
        }
        if (!empty($objectsObjects)) {
            throw new Exception('This component already exist for this object', 412);
        }

        $recursiveParentsListIds = $this->getRecursiveParentsListId($data['parent'], $data['anr'] ?? null);
        $childUuid = $data['child']['uuid'] ?? $data['child'];
        if (isset($recursiveParentsListIds[$childUuid])) {
            throw new Exception('You cannot create a cyclic dependency', 412);
        }

        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('MonarcObjectTable');

        // Ensure that we're not trying to add a specific item if the father is generic
        try {
            $father = $monarcObjectTable->getEntity($data['parent']);
            $child = $monarcObjectTable->getEntity($data['child']);
        } catch (MappingException $e) {
            $father = $monarcObjectTable->getEntity(['uuid' => $data['parent'], 'anr' => $data['anr']]);
            $child = $monarcObjectTable->getEntity(['uuid' => $data['child'], 'anr' => $data['anr']]);
        }

        // on doit déterminer si par voie de conséquence, cet objet ne va pas se retrouver dans un modèle dans lequel il n'a pas le droit d'être
        if ($context === MonarcObject::BACK_OFFICE) {
            /** @var Model[] $models */
            $models = $father->getAsset()->getModels();
            foreach ($models as $model) {
                $model->validateObjectAcceptance($child);
            }
        }

        if ($father->mode === ObjectObjectSuperClass::MODE_GENERIC
            && $child->mode === ObjectObjectSuperClass::MODE_SPECIFIC
        ) {
            throw new Exception('You cannot add a specific object to a generic parent', 412);
        }

        if (!empty($data['implicitPosition'])) {
            unset($data['position']);
        } elseif (!empty($data['position'])) {
            unset($data['implicitPosition']);
        }

        /** @var ObjectObjectSuperClass $entity */
        $objectObject = $this->get('entity');

        $objectObject->exchangeArray($data);

        $this->setDependencies($objectObject, $this->dependencies);

        $objectObject->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $id = $objectObjectTable->save($objectObject);

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

        $monarcObjectTable->save($child);

        //create instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        try {
            $instancesParent = $instanceTable->getEntityByFields(['object' => $father->getUuid()]);
        } catch (QueryException | MappingException $e) {
            $instancesParent = $instanceTable->getEntityByFields(['object' => ['uuid' => $father->getUuid(), 'anr' => $data['anr']]]);
        }

        foreach ($instancesParent as $instanceParent) {
            $anrId = $instanceParent->anr->id;

            $previousInstance = false;
            if ($data['implicitPosition'] == 3) {
                $previousObject = $objectObjectTable->get($data['previous'])['child'];
                try {
                    $instances = $instanceTable->getEntityByFields(['anr' => $data['anr'], 'object' => $previousObject->getUuid()]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'anr' => $data['anr'],
                        'object' => [
                            'uuid' => $previousObject->getUuid(),
                            'anr' => $data['anr']
                        ]
                    ]);
                }
                foreach ($instances as $instance) {
                    $previousInstance = $instance->id;
                }
            }

            $dataInstance = [
                'object' => $child->getUuid(),
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

            //if parent instance exist, create instance for child
            $eventManager = new EventManager($this->sharedManager, ['addcomponent']);
            $eventManager->trigger('createinstance', $this, compact(['anrId', 'dataInstance']));
        }

        return $id;
    }

    public function setSharedManager(SharedEventManager $sharedManager)
    {
        $this->sharedManager = $sharedManager;
    }

    /**
     * Fetch and returns the children of the object
     *
     * @param int $objectId The object ID
     *
     * @return array The children objects
     */
    public function getChildren($objectId, $anrId = null)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');
        if (is_object($objectId)) {
            $objectId = $objectId->getUuid();
        }
        if ($objectId !== null) {
            try {
                return $table->getEntityByFields(['parent' => $objectId], ['position' => 'DESC']);
            } catch (MappingException | QueryException $e) {
                return $table->getEntityByFields(['parent' => ['uuid' => $objectId, 'anr' => $anrId]], ['position' => 'DESC']);
            }
        }

        return null;
    }

    public function getRecursiveChildren(ObjectSuperClass $parent): array
    {
        /**
         * Infinite loop prevention.
         * Fetch all the parents relations of the current parent to check if its children are not parents.
         * @var ObjectObjectSuperClass[] $fathersOfFatherObjectObjects
         */
        // TODO: it was passed in the param, but we should not have any recursive things originally.
//        if (empty($excludeParentRelationObjectObjectIds)) {
//            $fathersOfFatherObjectObjects = $table->getEntityByFields(['child' => $queryParams['father']]);
//            if (!empty($fathersOfFatherObjectObjects)) {
//                $excludeParentRelationObjectObjectIds = array_column($fathersOfFatherObjectObjects, 'id');
//            }
//        }

        $children = $table->getEntityByFields($queryParams, ['position' => 'ASC']);

        $childrenResult = [];
        /** @var ObjectObjectSuperClass $child */
        foreach ($children as $child) {
            $queryParams = [
                'uuid' => $child->getChild()->getUuid(),
            ];
            if ($child->getChild()->getAnr() !== null) {
                $queryParams['anr'] = $child->getChild()->getAnr();
            }
            // TODO: this wont work...
            $objectChild = $this->get('MonarcObjectTable')->get($queryParams);

            /** Infinite loop prevention. */
            if (!in_array($child->getId(), $excludeParentRelationObjectObjectIds, true)) {
                $objectChild['children'] = $this->getRecursiveChildren(
                    $child->getChild()->getUuid(),
                    $child->getChild()->getAnr() ? $child->getChild()->getAnr()->getId() : null,
                    $excludeParentRelationObjectObjectIds
                );
            }
            $objectChild['component_link_id'] = $child->getId();
            $childrenResult[] = $objectChild;
        }

        return $childrenResult;
    }

    /**
     * Recursively fetches and returns the parent objects
     *
     * @param string $parent_id The parent object ID
     *
     * @return array The parents
     */
    public function getRecursiveParents($parent_id)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $parents = $table->getEntityByFields(['child' => $parent_id], ['position' => 'ASC']);
        $array_parents = [];

        foreach ($parents as $parent) {
            /** @var ObjectObjectSuperClass $parent */
            $parent_array = $parent->getJsonArray();

            $object_parent = $this->get('MonarcObjectTable')->get($parent_array['parent']);
            $object_parent['parents'] = $this->getRecursiveParents($parent_array['parent']);
            $object_parent['component_link_id'] = $parent_array['id'];
            $array_parents[] = $object_parent;
        }

        return $array_parents;
    }

    /**
     * Returns a list of parents recursively
     */
    private function getRecursiveParentsListId($parent, $anrId = null)
    {
        if ($anrId !== null && isset($parent['uuid'])) {
            $parentIds[$parent['uuid']] = $parent['uuid'];
            $queryParams = ['child' => $parent];
        } else {
            $parentIds[$parent] = $parent;
            $queryParams = ['child' => $parent];
        }

        if ($anrId !== null) {
            $queryParams['anr'] = $anrId;
        }

        /** @var ObjectObjectTable $table */
        $table = $this->get('table');
        $parents = $table->getEntityByFields($queryParams, ['position' => 'ASC']);

        /** @var ObjectObjectSuperClass $parentObject */
        foreach ($parents as $parentObject) {
            $parentParam = $parentObject->getParent()->getUuid();
            if ($anrId !== null) {
                $parentParam = [
                    'uuid' => $parentObject->getParent()->getUuid(),
                    'anr' => $anrId,
                ];
            }
            $parentIds = array_merge($parentIds, $this->getRecursiveParentsListId($parentParam, $anrId));
        }

        return $parentIds;
    }

    /**
     * Moves an object's position
     *
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

        $this->manageRelativePositionUpdate('parent', $entity, $direction);
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
            throw new Exception('Entity does not exist', 412);
        }

        //delete instance instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        try {
            $childInstances = $instanceTable->getEntityByFields(['object' => $objectObject->getChild()->getUuid()]);
            $fatherInstances = $instanceTable->getEntityByFields(['object' => $objectObject->getParent()->getUuid()]);
        } catch (QueryException | MappingException $e) {
            $childInstances = $instanceTable->getEntityByFields([
                'object' => [
                    'uuid' => $objectObject->getChild()->getUuid(),
                    'anr' => $objectObject->anr->id
                ]
            ]);
            $fatherInstances = $instanceTable->getEntityByFields([
                'object' => [
                    'uuid' => $objectObject->getParent()->getUuid(),
                    'anr' => $objectObject->anr->id
                ]
            ]);
        }

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
