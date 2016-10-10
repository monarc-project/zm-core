<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\AnrObjectCategoryTable;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\AssetTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ModelTable;
use MonarcCore\Model\Table\ObjectCategoryTable;
use MonarcCore\Model\Table\ObjectObjectTable;
use MonarcCore\Model\Table\ObjectTable;

/**
 * Object Service
 *
 * Class ObjectService
 * @package MonarcCore\Service
 */
class ObjectService extends AbstractService
{
    protected $objectObjectService;
    protected $modelService;

    protected $anrObjectCategoryEntity;

    protected $anrTable;
    protected $anrObjectCategoryTable;
    protected $assetTable;
    protected $categoryTable;
    protected $instanceTable;
    protected $modelTable;
    protected $objectObjectTable;
    protected $rolfTagTable;
    protected $amvTable;

    protected $filterColumns = [
        'name1', 'name2', 'name3', 'name4',
        'label1', 'label2', 'label3', 'label4',
    ];

    protected $dependencies = ['anr', 'asset', 'category', 'rolfTag'];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $asset
     * @param null $category
     * @param $lock
     * @return array
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $anr = null, $lock = null){

        $filterAnd = [];
        if ((!is_null($asset)) && ($asset != 0)) $filterAnd['asset'] = $asset;
        if ((!is_null($category)) && ($category != 0)) {

            $child = ($lock == 'true') ? [] : $this->get('categoryTable')->getDescendants($category);
            $child[] = $category;

            $filterAnd['category'] = $child;
        }
        $filterAnd['model'] = null;

        $objects = $this->getAnrObjects($page, $limit, $order, $filter, $filterAnd, $anr);

        $objectsArray = [];
        $rootArray = [];

        /** @var AssetTable $assetTable */
        $assetTable = $this->get('assetTable');
        /** @var ObjectCategoryTable $categoryTable */
        $categoryTable = $this->get('categoryTable');

        foreach($objects as $object) {
            if(!empty($object['asset'])){
                $object['asset'] = $assetTable->get($object['asset']->getId());
            }
            if(!empty($object['category'])){
                $object['category'] = $categoryTable->get($object['category']->getId());
            }

            $rootArray[$object['id']] = $object;
            $objectsArray[$object['id']] = $object;
        }

        $newRoot = [];
        foreach($rootArray as $value) {
            $newRoot[] = $value;
        }

        return $newRoot;
    }

    /**
     * Get Anr Objects
     *
     * @param $page
     * @param $limit
     * @param $order
     * @param $filter
     * @param $filterAnd
     * @param $anr
     * @return array|bool
     */
    public function getAnrObjects($page, $limit, $order, $filter, $filterAnd, $anr) {

        //retrieve all generic objects
        $filterAnd['mode'] = Object::IS_GENERIC;
        /** @var ObjectTable $objectTable */
        $objectTable = $this->get('table');
        $objects = $objectTable->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        //retrieve objects specific
        if ($anr) {
            /** @var ModelTable $modelTable */
            $modelTable = $this->get('modelTable');
            $models = $modelTable->getEntityByFields(['anr' => $anr]);

            /** @var AssetTable $assetTable */
            $assetTable = $this->get('assetTable');
            $assets = $assetTable->fetchAll();
            $assetsIds = [];
            foreach($models as $model) {
                foreach($assets as $asset) {
                    foreach($asset['models'] as $assetModel) {
                        if($model->id == $assetModel->id) {
                            $assetsIds[$asset['id']] = $asset['id'];
                        }
                    }
                }
            }

            $specificsObjects = $objectTable->getByAssets($assetsIds);

            foreach($specificsObjects as $key => $object) {
                $specificsObjects[$key] = $object->getJsonArray();
            }

            $objects = array_merge($objects, $specificsObjects);
        }

        return $objects;
    }

    /**
     * get Complete Entity
     * @param $id
     * @return mixed
     */
    public function getCompleteEntity($id, $context = Object::FRONT_OFFICE) {

        /** @var Object $object */
        $object = $this->get('table')->getEntity($id);
        $object_arr = $object->getJsonArray();

        // Retrieve children recursively
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $object_arr['children'] = $objectObjectService->getRecursiveChildren($object_arr['id']);

        // Retrieve parent recursively
        if ($context == Object::BACK_OFFICE) {
            $object_arr['parents'] = $objectObjectService->getRecursiveParents($object_arr['id']);
        }

        // Calculate the risks table
        //$object_arr['risks'] = $this->buildRisksTable($object, $mode);
        $object_arr['risks'] = $this->getRisks($object);
        $object_arr['oprisks'] = $this->getRisksOp($object);

        return $object_arr;
    }

    /**
     * Get Risks
     * 
     * @param $object
     * @return array
     */
    protected function getRisks($object) {

        /** @var AmvTable $amvTable */
        $amvTable = $this->get('amvTable');
        $amvs = $amvTable->getEntityByFields(['asset' =>$object->asset->id ]);

        $risks = [];
        foreach ($amvs as $amv) {

            $risks[] = [
                'id' => $amv->id,
                'threatDescription1' => $amv->threat->label1,
                'threatDescription2' => $amv->threat->label2,
                'threatDescription3' => $amv->threat->label3,
                'threatDescription4' => $amv->threat->label4,
                'threatRate' => '-',
                'vulnDescription1' => $amv->vulnerability->label1,
                'vulnDescription2' => $amv->vulnerability->label2,
                'vulnDescription3' => $amv->vulnerability->label3,
                'vulnDescription4' => $amv->vulnerability->label4,
                'vulnerabilityRate' => '-',
                'c_risk' => '-',
                'c_risk_enabled' => $amv->threat->c,
                'i_risk' => '-',
                'i_risk_enabled' => $amv->threat->i,
                'd_risk' => '-',
                'd_risk_enabled' => $amv->threat->d,
                'comment' => ''
            ];
        }

        return $risks;
    }

    protected function getRisksOp($object) {
        $riskOps = [];

        if (isset($object->asset)) {
            if ($object->asset->type == Asset::ASSET_PRIMARY) {
                if (!is_null($object->rolfTag)) {

                    //retrieve rolf risks
                    /** @var RolfTagTable $rolfTagTable */
                    $rolfTagTable = $this->get('rolfTagTable');
                    $rolfTag = $rolfTagTable->getEntity($object->rolfTag->id);

                    $rolfRisks = $rolfTag->risks;

                    foreach ($rolfRisks as $rolfRisk) {

                        $riskOps[] = [
                            'description1' => $rolfRisk->label1,
                            'description2' => $rolfRisk->label2,
                            'description3' => $rolfRisk->label3,
                            'description4' => $rolfRisk->label4,
                            'prob' => '-',
                            'r' => '-',
                            'o' => '-',
                            'l' => '-',
                            'p' => '-',
                            'risk' => '-',
                            'comment' => '',
                            't' => '',
                            'target' => '-',
                        ];
                    }
                }
            }
        }

        return $riskOps;
    }

    /**
     * Get Filtered Count
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $anr = null){

        $filterAnd = [];
        if ((!is_null($asset)) && ($asset != 0)) $filterAnd['asset'] = $asset;
        if ((!is_null($category)) && ($category != 0)) $filterAnd['category'] = $category;

        $result = $this->getAnrObjects($page, 0, $order, $filter, $filterAnd, $anr);

        return count($result);

        //return parent::getFilteredCount($page, $limit, $order, $filter, $filterAnd);
    }

    /**
     * Get generic by asset
     *
     * @param $asset
     * @return mixed
     */
    public function getGenericByAsset($asset) {
        return $this->get('table')->getGenericByAssetId($asset->getId());
    }

    /**
     * Recursive child
     *
     * @param $hierarchy
     * @param $parent
     * @param $childHierarchy
     * @return mixed
     */
    public function recursiveChild($hierarchy, $parent, &$childHierarchy, $objectsArray) {

        $childs = [];
        foreach($childHierarchy as $key => $link) {
            if ((int) $link['father'] == $parent) {
                $recursiveChild = $this->recursiveChild($hierarchy, $link['child'], $childHierarchy, $objectsArray);
                $recursiveChild['objectObjectId'] = $link['id'];
                $childs[] = $recursiveChild;
                unset($childHierarchy[$key]);
            }
        }

        $hierarchy = $objectsArray[$parent];
        $this->formatDependencies($hierarchy, $this->dependencies);
        if ($childs) {
            $hierarchy['childs'] = $childs;
            $hierarchy['childs'] = $childs;
        }

        return $hierarchy;
    }

    /**
     * @param $data
     * @param bool $last
     * @param string $context
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true, $context = Object::BACK_OFFICE) {

        //create object
        $object = $this->get('entity');

        //position
        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        if (!isset($data['implicitPosition'])) {
            throw  new \Exception('implicitPosition is missing', 412);
        } else  if ($data['implicitPosition'] == 3) {
            if (!$previous) {
                throw  new \Exception('previous is missing', 412);
            }
        }
        if ($previous) {
            $previousInstance = $this->get('table')->getEntity($previous);
        } else {
            $previousInstance = null;
        }

        if(empty($data['rolfTag'])){
            unset($data['rolfTag']);
        }

        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \Exception('Risk analysis not exist', 412);
            }
            $object->setAnr($anr);
        }

        $object->exchangeArray($data);

        //object dependencies
        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        $position = $this->managePosition('category', $object, (int) $data['category'], (int) $data['implicitPosition'], $previousInstance, 'post');
        $object->position = $position;
        unset($data['implicitPosition']);


        if (isset($data['source'])) {
            $object->source = $this->get('table')->getEntity($data['source']);
        }

        //security
        if ($object->mode == Object::IS_GENERIC && $object->asset->mode == Object::IS_SPECIFIC) {
            throw new \Exception("You can't have a generic object based on a specific asset", 412);
        }
        if (isset($data['modelId'])) {
            $this->get('modelService')->canAcceptObject($data['modelId'], $object, $context);
        }

        if ($context == Object::BACK_OFFICE) {
            //create object type bdc
            $id = $this->get('table')->save($object);

            //attach object to anr
            if (isset($data['modelId'])) {

                $model = $this->get('modelService')->getEntity($data['modelId']);

                if (!$model['anr']) {
                    throw new \Exception('No anr associated to this model', 412);
                }

                $this->attachObjectToAnr($object, $model['anr']->id);
            }
        } else {
            //create object type anr
            $id = $this->get('table')->save($object);
        }

        return $id;
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function update($id, $data){

        unset($data['anrs']);

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        $object = $this->get('table')->getEntity($id);
        if(!$object){
            throw new \Exception('Entity `id` not found.');
            return false;
        }
        $object->setDbAdapter($this->get('table')->getDb());
        $object->setLanguage($this->getLanguage());

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        if(empty($data['rolfTag'])){
            unset($data['rolfTag']);
        }

        if (isset($data['implicitPosition'])) {
            $data['position'] = $this->managePosition('category', $object, $data['category'], $data['implicitPosition'], $previous, 'update');
        }

        if(isset($data['mode']) && $data['mode'] != $object->get('mode')){
            /* on test:
            - que l'on a pas de parents GENERIC quand on passe de GENERIC à SPECIFIC
            - que l'on a pas de fils SPECIFIC quand on passe de SPECIFIC à GENERIC
            */
            if(!$this->checkModeIntegrity($object->get('id'), $object->get('mode'))){
                if($object->get('mode')==Object::IS_GENERIC){
                    throw new \Exception('You cannot set this object to specific mode because one of its parents is in generic mode.', 412);
                }else{
                    throw new \Exception('You cannot set this object to generic mode because one of its children is in specific mode.', 412);
                }
            }
        }

        $currentRootCategory = ($object->category->root) ? $object->category->root : $object->category;

        $object->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        $objectRootCategory = ($object->category->root) ? $object->category->root : $object->category;

        if ($currentRootCategory->id != $objectRootCategory->id) {

            //retrieve anrs for object
            foreach($object->anrs as $anr) {

                //retrieve number anr objects with the same root category than current object
                $nbObjectsSameOldRootCategory = 0;
                foreach($anr->objects as $anrObject) {
                    $anrObjectCategory = ($anrObject->category->root) ? $anrObject->category->root : $anrObject->category;
                    if (($anrObjectCategory->id == $objectRootCategory->id) && ($anrObject->id != $object->id)) {
                        $nbObjectsSameOldRootCategory++;
                    }
                }
                if (!$nbObjectsSameOldRootCategory) {
                    /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
                    $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
                    $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anr->id, 'category' => $currentRootCategory->id]);
                    foreach($anrObjectCategories as $anrObjectCategory) {
                        $anrObjectCategoryTable->delete($anrObjectCategory->id);
                        $this->managePosition('anr', $anrObjectCategory, $anr->id, null, null, 'delete', $anrObjectCategoryTable);
                    }
                }

                //retrieve number anr objects with the same category than current object
                $nbObjectsSameNewRootCategory = 0;
                foreach($anr->objects as $anrObject) {
                    $anrObjectCategory = ($anrObject->category->root) ? $anrObject->category->root : $anrObject->category;
                    if (($anrObjectCategory->id == $objectRootCategory->id) && ($anrObject->id != $object->id)) {
                        $nbObjectsSameNewRootCategory++;
                    }
                }if (!$nbObjectsSameNewRootCategory) {
                    /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
                    $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');

                    $class = $this->get('anrObjectCategoryEntity');
                    $anrObjectCategory = new $class();
                    $anrObjectCategory->anr = $anr;
                    $anrObjectCategory->category = $objectRootCategory;
                    $anrObjectCategory->position = $this->managePosition('anr', $anrObjectCategory, $anr->id, 2, null, 'post', $anrObjectCategoryTable);


                    $anrObjectCategoryTable->save($anrObjectCategory);
                }
            }
        }

        $this->get('table')->save($object);

        $this->instancesImpacts($object);

        return $id;
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data){

        $object = $this->get('table')->getEntity($id);
        $object->setLanguage($this->getLanguage());
        $object->exchangeArray($data, true);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        $this->get('table')->save($object);

        $this->instancesImpacts($object);

        return $id;
    }

    protected function instancesImpacts($object) {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instances = $instanceTable->getEntityByFields(['object' => $object]);
        foreach($instances as $instance) {
            $modifyInstance = false;
            for($i=1; $i<=4; $i++) {
                $name = 'name' . $i;
                if ($instance->$name != $object->$name) {
                    $modifyInstance = true;
                    $instance->$name = $object->$name;
                }
                $label = 'label' . $i;
                if ($instance->$label != $object->$label) {
                    $modifyInstance = true;
                    $instance->$label = $object->$label;
                }
            }
            if ($modifyInstance) {
                $instanceTable->save($instance);
            }
        }
    }

    protected function checkModeIntegrity($id, $mode){
        $objectObjectService = $this->get('objectObjectService');
        switch ($mode) {
            case Object::IS_GENERIC:
                $objects = $objectObjectService->getRecursiveParents($id);
                $field = 'parents';
                break;
            case Object::IS_SPECIFIC:
                $objects = $objectObjectService->getRecursiveChildren($id);
                $field = 'children';
                break;
            default:
                return false;
                break;
        }
        return $this->checkModeIntegrityRecursive($objects,$mode,$field);
    }
    private function checkModeIntegrityRecursive($objects = array(), $mode, $field){
        foreach($objects as $p){
            if($p['mode'] == $mode){
                return false;
            }elseif(!empty($p[$field]) && !$this->checkModeIntegrityRecursive($p[$field],$mode, $field)){
                return false;
            }
        }
        return true;
    }

    /**
     * Delete
     *
     * @param $id
     * @throws \Exception
     */
    public function delete($id) {

        $entity = $this->getEntity($id);
        if(!$entity){
            throw new \Exception('Entity `id` not found.');
        }

        $objectCategoryId = $entity['category']->id;
        $position = $entity['position'];

        $this->get('table')->changePositionsByParent('category', $objectCategoryId, $position, 'down', 'after');

        $this->get('table')->delete($id);
    }

    /**
     * Duplicate
     *
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function duplicate($data) {

        $entity = $this->getEntity($data['id']);

        if(!$entity){
            throw new \Exception('Entity `id` not found.');
        }

        $keysToRemove = ['id','position', 'creator', 'createdAt', 'updater', 'updatedAt', 'inputFilter', 'language', 'dbadapter', 'parameters'];

        foreach($keysToRemove as $key) {
            unset($entity[$key]);
        }

        foreach($this->dependencies as $dependency) {
            if (is_object($entity[$dependency])) {
                $entity[$dependency] = $entity[$dependency]->id;
            }
        }

        $keys = array_keys($entity);

        foreach($keys as $key) {
            if (is_null($entity[$key])) {
                unset($entity[$key]);
            }
        }

        $entity['implicitPosition'] = isset($data['implicitPosition']) ? $data['implicitPosition'] : 2;
        $entity['name1'] = $entity['name1'].' (copy)';
        $entity['name2'] = $entity['name2'].' (copy)';
        $entity['name3'] = $entity['name3'].' (copy)';
        $entity['name4'] = $entity['name4'].' (copy)';

        return $this->create($entity);
    }

    /**
     * Attach Object To Anr
     *
     * @param $object
     * @param $anrId
     * @param null $parent
     * @return null
     * @throws \Exception
     */
    public function attachObjectToAnr($object, $anrId, $parent = null, $objectObjectPosition = null)
    {
        //object
        /** @var ObjectTable $table */
        $table = $this->get('table');

        if (!is_object($object)) {
            $object = $table->getEntity($object);
        }

        if (!$object) {
            throw new \Exception('Object not exist', 412);
        }

        //retrieve anr
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);
        if (!$anr) {
            throw new \Exception('Risk analysis not exist', 412);
        }

        //add anr to object
        $object->addAnr($anr);

        //save object
        $id = $table->save($object);

        //retrieve root category
        /** @var ObjectCategoryTable $objectCategoryTable */
        $objectCategoryTable = $this->get('categoryTable');
        $objectCategory = $objectCategoryTable->getEntity($object->category->id);
        $objectRootCategoryId = ($objectCategory->root) ? $objectCategory->root->id : $objectCategory->id;

        //add root category to anr
        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'category' => $objectRootCategoryId]);
        if (!count($anrObjectCategories)) {
            $class = $this->get('anrObjectCategoryEntity');
            $anrObjectCategory = new $class();
            $anrObjectCategory->anr = $anr;
            $anrObjectCategory->category = ($object->category->root) ? $object->category->root : $object->category;
            $anrObjectCategory->position = $this->managePosition('anr', $anrObjectCategory, $anrId, 2, null, 'post', $anrObjectCategoryTable);
            $anrObjectCategoryTable->save($anrObjectCategory);
        }

        //children
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($object->id);
        foreach ($children as $child) {
            $childObject = $table->getEntity($child->child->id);
            $this->attachObjectToAnr($childObject, $anrId, $id, $child->position);
        }

        return $id;
    }

    /**
     * Detach Object To Anr
     *
     * @param $objectId
     * @param $anrId
     * @throws \Exception
     */
    public function detachObjectToAnr($objectId, $anrId) {

        //retrieve object
        /** @var ObjectTable $table */
        $table = $this->get('table');
        $object = $table->getEntity($objectId);
        if (!$object) {
            throw new \Exception('Object not exist', 412);
        }

        //verify object is not a composant
        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('objectObjectTable');
        $liaisons = $objectObjectTable->getEntityByFields(['anr' => $anrId, 'child' => $objectId]);
        if (count($liaisons)) {
            throw new \Exception("You can't detach this object because an other use it. Clean dependencies before detach it.", 412);
        }

        //retrieve anr
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);
        if (!$anr) {
            throw new \Exception('Risk analysis not exist', 412);
        }

        //retrieve number anr objects with the same root category than current objet
        $nbObjectsSameRootCategory = 0;
        $objectRootCategory = ($object->category->root) ? $object->category->root : $object->category;
        foreach($anr->objects as $anrObject) {

            $anrObjectRootCategory = ($anrObject->category->root) ? $anrObject->category->root : $anrObject->category;

            if (($anrObjectRootCategory->id == $objectRootCategory->id) && ($anrObject->id != $object->id)) {
                $nbObjectsSameRootCategory++;
            }
        }

        //if the last object of the category in the anr, delete category from anr
        if (!$nbObjectsSameRootCategory) {
            //anrs objects categories
            /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
            $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
            $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'category' => $objectRootCategory->id]);

            foreach( $anrObjectCategories as $anrObjectCategory) {
                $this->managePosition('anr', $anrObjectCategory, $anrId, null, null, 'delete', $anrObjectCategoryTable);
                $anrObjectCategoryTable->delete($anrObjectCategory->id);
            }
        }

        //delete instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instances = $instanceTable->getEntityByFields(['object' => $objectId]);
        foreach($instances as $instance) {
            $instanceTable->delete($instance->id);
        }

        //detach object
        /** @var ObjectTable $table */
        $table = $this->get('table');
        $object = $table->getEntity($objectId);
        $anrs = [];
        foreach($object->anrs as $anr) {
            if ($anr->id != $anrId) {
                $anrs[] = $anr;
            }
        }
        $object->anrs = $anrs;
        $table->save($object);
    }

    /**
     * Get Categories Library By Anr
     *
     * @param $anrId
     * @return mixed
     */
    public function getCategoriesLibraryByAnr($anrId) {

        //retrieve objects
        $anrObjects = [];
        $objectsCategories = [];
        /** @var ObjectTable $objectTable */
        $objectTable = $this->get('table');
        $objects = $objectTable->fetchAll();
        foreach($objects as $object) {
            if ($object['anrs']) {
                foreach($object['anrs'] as $anr) {
                    if ($anr->id == $anrId) {
                        $anrObjects[] = $object;
                        $objectsCategories[$object['category']->id] = $object['category'];
                        break;
                    }
                }
            }
        }

        $parents = [];
        foreach($objectsCategories as $category) {
            $this->getRecursiveParents($category, $parents);
        }

        $objectsCategories = $objectsCategories + $parents;

        foreach ($objectsCategories as $key => $objectsCategory) {
            $objectsCategories[$key] = $objectsCategory->getJsonArray();
        }

        foreach($anrObjects as $anrObject) {
            $objectsCategories[$anrObject['category']->id]['objects'][] = $anrObject;
        }

        //retrieve categories
        $anrObjectsCategories = [];
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId], ['position' => 'ASC']);
        foreach($anrObjectCategories as $anrObjectCategory) {
            $anrObjectsCategories[$anrObjectCategory->id] = $this->getChildren($anrObjectCategory->category->getJsonArray(), $objectsCategories);

        }


        foreach($anrObjectsCategories as $key => $anrObjectCategory) {
            foreach ($anrObjects as $anrObject) {
                if ($anrObjectCategory['id'] == $anrObject['category']->id) {
                    $anrObjectsCategories[$key]['objects'][] = $anrObject;
                }
            }
        }

        return $anrObjectsCategories;
    }

    /**
     * Get Recursive Parents
     *
     * @param $category
     * @param $array
     */
    public function getRecursiveParents($category, &$array ){

        if ($category->parent) {
            /** @var ObjectCategoryTable $table */
            $table = $this->get('categoryTable');
            $parent = $table->getEntity($category->parent->id);

            $array[$parent->id] = $parent;

            $this->getRecursiveParents($parent, $array);
        }
    }

    /**
     * Get Children
     *
     * @param $parentObjectCategory
     * @param $objectsCategories
     * @return mixed
     */
    public function getChildren($parentObjectCategory, &$objectsCategories) {

        $currentObjectCategory = $parentObjectCategory;
        unset($objectsCategories[$parentObjectCategory['id']]);

        foreach($objectsCategories as $objectsCategory) {
            if ($objectsCategory['parent']) {
                if ($objectsCategory['parent']->id == $parentObjectCategory['id']) {
                    $objectsCategory = $this->getChildren($objectsCategory, $objectsCategories);
                    unset($objectsCategory['__initializer__']);
                    unset($objectsCategory['__cloner__']);
                    unset($objectsCategory['__isInitialized__']);
                    $currentObjectCategory['child'][] = $objectsCategory;
                }
            }
        }

        return $currentObjectCategory;
    }

    public function export($data) {
        if (empty($data['password'])) {
            throw new \Exception('You must type in a password', 412);
        }

        $entity = $this->getEntity($data['id']);

        if (!$entity) {
            throw new \Exception('Entity `id` not found.');
        }

        $output = json_encode($entity);
        return $this->encrypt($output);
    }

    protected function encrypt($data) {
        return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, Object::SALT, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }

    protected function decrypt($data) {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, Object::SALT, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }
}
