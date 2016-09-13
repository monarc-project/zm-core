<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\AssetTable;
use MonarcCore\Model\Table\ObjectCategoryTable;
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

    protected $anrTable;
    protected $assetTable;
    protected $categoryTable;
    protected $rolfTagTable;
    protected $amvTable;

    protected $filterColumns = [
        'name1', 'name2', 'name3', 'name4',
        'label1', 'label2', 'label3', 'label4',
    ];

    protected $dependencies = ['asset', 'category', 'rolfTag'];

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
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $lock = null){

        $filterAnd = [];
        if ((!is_null($asset)) && ($asset != 0)) $filterAnd['asset'] = $asset;
        if ((!is_null($category)) && ($category != 0)) {

            $child = ($lock == 'true') ? [] : $this->get('categoryTable')->getDescendants($category);
            $child[] = $category;

            $filterAnd['category'] = $child;
        }
        $filterAnd['model'] = null;


        //retrieve all objects
        $objects = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

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

    public function getCompleteEntity($id) {
        /** @var Object $entity */
        $entity = $this->get('table')->getEntity($id);
        $entity_arr = $entity->getJsonArray();

        // Retrieve children recursively
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $entity_arr['children'] = $objectObjectService->getRecursiveChildren($entity_arr['id']);

        // Calculate the risks table
        //$entity_arr['risks'] = $this->buildRisksTable($entity, $mode);
        $entity_arr['risks'] = $this->getRisks($entity);
        $entity_arr['oprisks'] = $this->getRisksOp($entity);

        return $entity_arr;
    }

    /*
    protected function buildRisksTable($entity, $mode) {
        $output = [];

        // First, get all the AMV links for this object's asset
        $amvs = $this->amvTable->findByAsset($entity->getAsset()->getId());

        foreach ($amvs as $amv) {
            $amv_array = $amv->getJsonArray();
            $this->formatDependencies($amv_array, ['asset', 'threat', 'vulnerability']);

            $prob = null;
            $qualif = null;
            $c_risk = null;
            $i_risk = null;
            $d_risk = null;
            $comment = null;
            $risk_id = null;

            if ($mode == 'anr') {

                // Fetch the risk assessment information from the DB for that AMV link
                $risk = $this->objectRiskTable->getEntityByFields([ //'anr' => $entity->getAnr() ? $entity->getAnr()->getId() : null,
                    'object' => $entity->getId(),
                    'amv' => $amv_array['id'],
                    'asset' => $amv_array['asset']['id'],
                    'threat' => $amv_array['threat']['id'],
                    'vulnerability' => $amv_array['vulnerability']['id']
                ]);

                if (count($risk) == 1) {
                    // If we have some info, display them here. Otherwise, we'll only display a placeholder.
                    $risk = $risk[0];
                    $risk_id = $risk->getId();
                    $prob = (string) $risk->getThreatRate();
                    $qualif = (string) $risk->getVulnerabilityRate();
                    $comment = $risk->getComment();
                } else {
                    // We have NO risk data for this, create the line!
                    /** @var ObjectRisk $new_risk_entity */
                    /*$new_risk_entity = new ObjectRisk();
                    $new_risk_entity->setAnr($entity->getAnr());
                    $new_risk_entity->setObject($entity);
                    $new_risk_entity->setAmv($amv);
                    $new_risk_entity->setAsset($amv->getAsset());
                    $new_risk_entity->setThreat($amv->getThreat());
                    $new_risk_entity->setVulnerability($amv->getVulnerability());

                    $risk_id = $this->get('objectRiskTable')->save($new_risk_entity);

                    $prob = "0";
                    $qualif = "0";
                    $comment = '';
                }

                if ($prob > 0 && $qualif > 0) {
                    if ($amv_array['threat']['c']) {
                        $c_risk = $c_impact * $prob * $qualif;
                    }
                    if ($amv_array['threat']['i']) {
                        $i_risk = $c_impact * $prob * $qualif;
                    }
                    if ($amv_array['threat']['d']) {
                        $d_risk = $c_impact * $prob * $qualif;
                    }
                }
            }

            $output[] = array(
                'id' => $risk_id,
                'threatDescription' => $amv_array['threat']['label1'],
                'threatRate' => $prob,
                'vulnDescription' => $amv_array['vulnerability']['label1'],
                'vulnerabilityRate' => $qualif,
                'c_risk' => $c_risk,
                'c_risk_enabled' => $amv_array['threat']['c'],
                'i_risk' => $i_risk,
                'i_risk_enabled' => $amv_array['threat']['i'],
                'd_risk' => $d_risk,
                'd_risk_enabled' => $amv_array['threat']['d'],
                'comment' => $comment
            );
        }

        return $output;
    }
    */

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
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null){

        $filterAnd = [];
        if ((!is_null($asset)) && ($asset != 0)) $filterAnd['asset'] = $asset;
        if ((!is_null($category)) && ($category != 0)) $filterAnd['category'] = $category;

        return parent::getFilteredCount($page, $limit, $order, $filter, $filterAnd);
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
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data, $context = Object::BACK_OFFICE) {

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

        $entity = $this->get('table')->getEntity($id);
        if(!$entity){
            throw new \Exception('Entity `id` not found.');
            return false;
        }
        $entity->setLanguage($this->getLanguage());

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        if(empty($data['rolfTag'])){
            unset($data['rolfTag']);
        }

        if (isset($data['implicitPosition'])) {
            $data['position'] = $this->managePosition('category', $entity, $data['category'], $data['implicitPosition'], $previous, 'update');
        }

        if(isset($data['mode']) && $data['mode'] != $entity->get('mode')){
            /* on test:
            - que l'on a pas de parents GENERIC quand on passe de GENERIC à SPECIFIC
            - que l'on a pas de fils SPECIFIC quand on passe de SPECIFIC à GENERIC
            */
            if(!$this->checkModeIntegrity($entity->get('id'),$entity->get('mode'))){
                if($entity->get('mode')==Object::IS_GENERIC){
                    throw new \Exception('Entity parent with generic mode is defined.');
                }else{
                    throw new \Exception('Entity child with specific mode is defined.');
                }
                return false;
            }
        }

        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        //add and remove parent is manage in service objects-components

        return $this->get('table')->save($entity);
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

        //retrieve anr
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);

        //add anr to object
        $object->addAnr($anr);

        //save object
        $id = $table->save($object);

        //parent
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        if ($parent) {
            $data = [
                'anr' => $anrId,
                'father' => $parent,
                'child' => $id,
            ];

            if ($objectObjectPosition) {
                $data['position'] = $objectObjectPosition;
            }

            $objectObjectService->create($data);
        }


        //children
        $children = $objectObjectService->getChildren($object->id);
        foreach ($children as $child) {

            $childEntity = $table->getEntity($child->child->id);

            $this->attachObjectToAnr($childEntity, $anrId, $id, $child->position);

        }

        return $id;
    }

    /**
     * Get Categories Library By Anr
     *
     * @param $anrId
     * @return mixed
     */
    public function getCategoriesLibraryByAnr($anrId) {

        //retrieve anr objects
        /** @var ObjectTable $objectTable */
        $objectTable = $this->get('table');
        $objects = $objectTable->fetchAll();

        $anrObjects = [];
        $anrObjectsCategories = [];
        foreach($objects as $object) {
            if ($object['anrs']) {
                foreach($object['anrs'] as $anr) {
                    if ($anr->id == $anrId) {
                        $anrObjects[] = $object;
                        $anrObjectsCategories[$object['category']->id] = $object['category'];
                        break;
                    }
                }
            }
        }

        $parents = [];
        foreach($anrObjectsCategories as $category) {
            $this->getRecursiveParents($category, $parents);
        }

        $anrObjectsCategories = $anrObjectsCategories + $parents;

        foreach($anrObjectsCategories as $key => $anrObjectsCategory) {
            $anrObjectsCategories[$key] = $anrObjectsCategory->getJsonArray();
        }

        foreach($anrObjects as $anrObject) {
            $anrObjectsCategories[$anrObject['category']->id]['objects'][] = $anrObject;
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