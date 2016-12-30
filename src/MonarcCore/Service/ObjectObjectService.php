<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\AbstractEntity;
use MonarcCore\Model\Entity\Object;
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
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true, $context = Object::BACK_OFFICE) {
        if ($data['father'] == $data['child']) {
            throw new \Exception("You cannot add yourself as a component", 412);
        }

        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('table');

        //verify child not already existing
        $objectsObjects = $objectObjectTable->getEntityByFields(['anr' => 'null', 'father' => $data['father'], 'child' => $data['child']]);
        if (count($objectsObjects)) {
            throw new \Exception('This component already exist for this object', 412);
        }

        $recursiveParentsListId = [];
        $this->getRecursiveParentsListId($data['father'], $recursiveParentsListId);

        if (in_array($data['child'], $recursiveParentsListId)) {
            throw new \Exception("You cannot create a cyclic dependency", 412);
        }

        /** @var ObjectTable $objectTable */
        $objectTable = $this->get('objectTable');

        // Ensure that we're not trying to add a specific item if the father is generic
        $father = $objectTable->getEntity($data['father']);
        $child = $objectTable->getEntity($data['child']);

        // on doit déterminer si par voie de conséquence, cet objet ne va pas se retrouver dans un modèle dans lequel il n'a pas le droit d'être
        if ($context == Object::BACK_OFFICE) {
            $models = $father->get('asset')->get('models');
            foreach($models as $m){
                $this->get('modelTable')->canAcceptObject($m->get('id'), $child, $context);
            }
        }

        if ($father->mode == ObjectObject::MODE_GENERIC && $child->mode == ObjectObject::MODE_SPECIFIC) {
            throw new \Exception("You cannot add a specific object to a generic parent", 412);
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
        // $fatherValue = $entity->get('father');
        // if (!empty($fatherValue)) {
        //     $fatherEntity = $objectTable->getEntity($fatherValue);
        //     $entity->setFather($fatherEntity);
        // }

        // $childValue = $entity->get('child');
        // if (!empty($childValue)) {
        //     $childEntity = $objectTable->getEntity($childValue);
        //     $entity->setChild($childEntity);
        // }

        $id = $objectObjectTable->save($entity);

        //link to anr
        $parentAnrs = [];
        $childAnrs = [];
        if($father->anrs){
            foreach ($father->anrs as $anr) {
                $parentAnrs[] = $anr->id;
            }
        }
        if($child->anrs){
            foreach ($child->anrs as $anr) {
                $childAnrs[] = $anr->id;
            }
        }

        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        foreach($parentAnrs as $anrId) {
            if (!in_array($anrId, $childAnrs)) {
                $child->addAnr($anrTable->getEntity($anrId));
            }
        }

        $objectTable->save($child);

        //create instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instancesParent = $instanceTable->getEntityByFields(['object' => $father->id]);

        foreach($instancesParent as $instanceParent) {
            $anrId = $instanceParent->anr->id;

            $previousInstance = false;
            if ($data['implicitPosition'] == 3) {
                $previousObject = $objectObjectTable->get($previous)['child'];
                $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $previousObject->id]);
                foreach($instances as $instance) {
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
     * Get Childs
     *
     * @param $objectId
     * @return mixed
     */
    public function getChildren($objectId)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        return $table->getEntityByFields(array('father' => $objectId), array('position' => 'DESC'));
    }

    public function getRecursiveChildren($fatherId, $anrId = null) {
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

    public function getRecursiveParentsListId($parentId, &$array){

        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        $parents = $table->getEntityByFields(array('child' => $parentId), array('position' => 'ASC'));

        foreach ($parents as $parent) {
            $array[] = $parent->father->id;
            $this->getRecursiveParents($parent->father->id, $array);
        }
    }

    public function moveObject($id, $direction) {
        $entity = $this->get('table')->getEntity($id);

        if ($entity->position == 1 && $direction == 'up') {
            // Nothing to do
            return;
        }

        $this->manageRelativePositionUpdate('father', $entity, $direction);
    }

    /**
     * Delete
     *
     * @param $id
     * @throws \Exception
     */
    public function delete($id) {

        /** @var ObjectObjectTable $table */
        $table = $this->get('table');
        $objectObject = $table->getEntity($id);

        if (!$objectObject) {
            throw new \Exception('Entity does not exist', 412);
        }

        //delete instance instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $childInstances =  $instanceTable->getEntityByFields(['object' => $objectObject->child->id]);
        $fatherInstances =  $instanceTable->getEntityByFields(['object' => $objectObject->father->id]);

        foreach($childInstances as $childInstance) {
            foreach($fatherInstances as $fatherInstance) {
                if ($childInstance->parent) {
                    if ($childInstance->parent->id == $fatherInstance->id) {
                        $childInstance->parent = null;
                        $childInstance->root = null;
                        $instanceTable->delete($childInstance->id);
                    }
                }
            }
        }

        parent::delete($id);
    }
}
