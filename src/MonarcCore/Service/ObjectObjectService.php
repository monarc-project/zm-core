<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\MonarcObject;
use MonarcCore\Model\Entity\ObjectObject;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ObjectObjectTable;
use Zend\EventManager\EventManager;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;

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
    protected $MonarcObjectTable;
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
        try{
          $objectsObjects = $objectObjectTable->getEntityByFields(['anr' => (empty($data['anr']) ? null : $data['anr']), 'father' => $data['father'], 'child' => $data['child']]);
        }catch(QueryException $e){
          $objectsObjects = $objectObjectTable->getEntityByFields(['anr' => (empty($data['anr']) ? null : $data['anr']),
          'father' => ['anr' => $data['anr'], 'uuid' => $data['father']],
          'child' => ['anr' => $data['anr'],'uuid' => $data['child']]]);
        }
        if (count($objectsObjects)) {
            throw new \MonarcCore\Exception\Exception('This component already exist for this object', 412);
        }

        $recursiveParentsListId = [];
        $this->getRecursiveParentsListId($data['father'], $recursiveParentsListId,$data['anr'] );

        if (isset($recursiveParentsListId[$data['child']])) {
            throw new \MonarcCore\Exception\Exception("You cannot create a cyclic dependency", 412);
        }

        /** @var MonarcObjectTable $MonarcObjectTable */
        $MonarcObjectTable = $this->get('MonarcObjectTable');

        // Ensure that we're not trying to add a specific item if the father is generic
        try{
          $father = $MonarcObjectTable->getEntity($data['father']);
          $child = $MonarcObjectTable->getEntity($data['child']);
        }catch(MappingException $e){
          $father = $MonarcObjectTable->getEntity(['uuid' => $data['father'], 'anr' => $data['anr']]);
          $child = $MonarcObjectTable->getEntity(['uuid' => $data['child'], 'anr' => $data['anr']]);
        }

        // on doit déterminer si par voie de conséquence, cet objet ne va pas se retrouver dans un modèle dans lequel il n'a pas le droit d'être
        if ($context == MonarcObject::BACK_OFFICE) {
            $models = $father->get('asset')->get('models');
            foreach ($models as $m) {
                $this->get('modelTable')->canAcceptObject($m->get('id'), $child, $context);
            }
        }

        if ($father->mode == ObjectObject::MODE_GENERIC && $child->mode == ObjectObject::MODE_SPECIFIC) {
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

        $MonarcObjectTable->save($child);

        //create instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        try{
          $instancesParent = $instanceTable->getEntityByFields(['object' => $father->uuid->toString()]);
        }catch(QueryException $e){
          $instancesParent = $instanceTable->getEntityByFields(['object' => ['uuid' => $father->uuid->toString(), 'anr' => $data['anr']]]);
        }

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
    public function getChildren($objectId, $anrId = null)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        file_put_contents('php://stderr', print_r($objectId, TRUE).PHP_EOL);
        if($objectId != null)
        {
          try{
            return $table->getEntityByFields(['father' => $objectId], ['position' => 'DESC']);
          }catch(MappingException $e){
            return $table->getEntityByFields(['father' => ['uuid' => $objectId, 'anr' => $anrId]], ['position' => 'DESC']);
          }
        }else {
          return null;
        }

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

        $filters = ['f.uuid' => $fatherId];
        if (!is_null($anrId)) {
            $filters['anr'] = $anrId;
            $filters['f.anr'] = $anrId;

            //$filters['father.uuid']  = ['uuid' => $fatherId, 'anr' => $anrId];
        }
        $filterJoin = $filterLeft = $filtersCol = [];
         list($filterJoin,$filterLeft,$filtersCol) = $this->get('entity')->getFiltersForService();
        $children = $table->fetchAllFiltered([], 1, 0, null, null , $filters , $filterJoin, $filterLeft);
        //getEntityByFields($filters, ['position' => 'ASC']);
        $array_children = [];

        foreach ($children as $child) {
            /** @var ObjectObject $child */

            $child_array = $child;
            try{
              $object_child = $this->get('MonarcObjectTable')->get($child_array['child']->uuid->toString());
            }catch(MappingException $e){
              $object_child = $this->get('MonarcObjectTable')->get(['uuid' => $child_array['child']->uuid->toString(), 'anr' =>$child['child']->anr->id ]);
            }
            $object_child['children'] = $this->getRecursiveChildren($child['child']->uuid->toString(),$child['child']->anr->id);
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

            $object_parent = $this->get('MonarcObjectTable')->get($parent_array['father']);
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
    public function getRecursiveParentsListId($parentId, &$array, $anrId = null)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        try{
          $parents = $table->getEntityByFields(['child' => $parentId], ['position' => 'ASC']);
        }catch(QueryException $e){
          $parents = $table->getEntityByFields(['child' => ['uuid' => $parentId, 'anr' =>$anrId ]], ['position' => 'ASC']);
        }
        $array[$parentId] = $parentId;

        foreach ($parents as $parent) {
            $this->getRecursiveParentsListId($parent->father->uuid, $array, $anrId);
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
        try{
          $childInstances = $instanceTable->getEntityByFields(['object' => $objectObject->child->uuid->toString()]);
          $fatherInstances = $instanceTable->getEntityByFields(['object' => $objectObject->father->uuid->toString()]);
        }catch(QueryException $e){
          $childInstances = $instanceTable->getEntityByFields(['object' => ['uuid' => $objectObject->child->uuid->toString(), 'anr' => $objectObject->anr->id]]);
          $fatherInstances = $instanceTable->getEntityByFields(['object' => ['uuid' => $objectObject->father->uuid->toString(), 'anr' => $objectObject->anr->id]]);
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
