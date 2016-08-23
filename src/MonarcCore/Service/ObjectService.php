<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Entity\ObjectRisk;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\ObjectRiskTable;
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

    // Must be 16, 24 or 32 characters
    const SALT = '__$$00_C4535_5M1L3_00$$__XMP0)XW';

    protected $assetTable;
    protected $categoryTable;
    protected $rolfTagTable;
    /** @var AmvTable */
    protected $amvTable;
    /** @var ObjectRiskTable */
    protected $objectRiskTable;
    protected $objectRiskService;
    /** @var ObjectRisk */
    protected $riskEntity;

    const BDC = 'bdc';
    const ANR = 'anr';

    const SCOPE_LOCAL = 1;
    const SCOPE_GLOBAL = 2;

    protected $filterColumns = [
        'name1', 'name2', 'name3', 'name4',
        'label1', 'label2', 'label3', 'label4',
    ];

    protected $dependencies = ['asset', 'category', 'rolfTag'];

    protected $forceType = 'bdc';

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
        $filterAnd['type'] = $this->forceType;
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
        foreach($objects as $object) {
            $rootArray[$object['id']] = $object;
            $objectsArray[$object['id']] = $object;
        }

        $newRoot = [];
        foreach($rootArray as $value) {
            $newRoot[] = $value;
        }

        return $newRoot;
    }

    public function getEntity($id, $mode = 'bdc') {
        /** @var Object $entity */
        $entity = $this->get('table')->getEntity($id);
        $entity_arr = $entity->getJsonArray();

        // Retrieve children recursively
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $entity_arr['children'] = $objectObjectService->getRecursiveChildren($entity_arr['id']);

        // Calculate the risks table
        $entity_arr['risks'] = $this->buildRisksTable($entity, $mode);

        return $entity_arr;
    }

    protected function buildRisksTable($entity, $mode) {
        $output = [];

        // First, get all the AMV links for this object's asset
        $amvs = $this->amvTable->findByAsset($entity->getAsset()->getId());

        foreach ($amvs as $amv) {
            $amv_array = $amv->getJsonArray();
            $this->formatDependencies($amv_array, ['asset', 'threat', 'vulnerability']);

            $c_impact = -1;
            $i_impact = -1;
            $d_impact = -1;
            $prob = null;
            $qualif = null;
            $c_risk = null;
            $i_risk = null;
            $d_risk = null;
            $comment = null;
            $risk_id = null;

            if ($mode == 'anr') {
                $c_impact = $entity->getC();
                $i_impact = $entity->getI();
                $d_impact = $entity->getD();

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
                    $new_risk_entity = new ObjectRisk();
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
                'c_impact' => $c_impact,
                'i_impact' => $i_impact,
                'd_impact' => $d_impact,
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
        $filterAnd['type'] = $this->forceType;

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
     * Get anr by asset
     *
     * @param $asset
     * @return mixed
     */
    public function getAnrByAsset($asset) {
        return $this->get('table')->getAnrByAssetId($asset->getId());
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
    public function create($data, $context = self::BACK_OFFICE) {

        //position
        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        if (!isset($data['implicitPosition'])) {
            throw  new \Exception('implicitPosition is missing', 412);
        } else  if ($data['implicitPosition'] == 3) {
            if (!$previous) {
                throw  new \Exception('previous is missing', 412);
            }
        }
        $position = $this->managePositionCreation('category', $data['category'], (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;
        unset($data['implicitPosition']);
        $data['type'] = $this->forceType;

        if(empty($data['rolfTag'])){
            unset($data['rolfTag']);
        }

        //create object
        $object = $this->get('entity');
        $object->exchangeArray($data);

        //object dependencies
        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        if (isset($data['source'])) {
            $object->source = $this->get('table')->getEntity($data['source']);
        }

        //security
        if ($object->mode == self::IS_GENERIC && $object->asset->mode == self::IS_SPECIFIC) {
            throw new \Exception("You can't have a generic object based on a specific asset", 412);
        }
        if (isset($data['modelId'])) {
            $this->get('modelService')->canAcceptObject($data['modelId'], $object, $context);
        }

        if ($context == self::BACK_OFFICE) {
            //create object type bdc
            $object->type = self::BDC;
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
        if(!$entity || $entity->get('type') != $this->forceType){
            throw new \Exception('Entity `id` not found.');
            return false;
        }
        $entity->setLanguage($this->getLanguage());

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        if(empty($data['rolfTag'])){
            unset($data['rolfTag']);
        }

        if (isset($data['implicitPosition'])) {
            $data['position'] = $this->managePositionUpdate('category', $entity, $data['category'], $data['implicitPosition'], $previous);
        }

        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        //add and remove parent is manage in service objects-components

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     * @throws \Exception
     */
    public function delete($id) {

        $entity = $this->getEntity($id);
        if(!$entity || $entity['type'] != $this->forceType){
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

        if(!$entity || $entity['type'] != $this->forceType){
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

        //verify object not exist to anr
        $existingObjectsToAnr = $table->getEntityByFields(array('source' => $object->id, 'anr' => $anrId, 'type' => self::ANR));
        if (count($existingObjectsToAnr)) {
            throw new \Exception('This object already exists in the current risk analysis', 412);
        }

        $anrObject = clone $object;
        $anrObject->id = null;
        $anrObject->type = self::ANR;
        $anrObject->anr = $anrId;
        $anrObject->source = $this->get('table')->getEntity($object->id);

        $id = $table->save($anrObject);

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

        //retrieve risks
        /** @var AmvTable $amvTable */
        $amvTable = $this->get('amvTable');
        $amvs = $amvTable->findByAsset($object->asset->id);
        foreach ($amvs as $amv) {
            if (is_null($amv->anr)) {
                $data = [
                    'anr' => $anrId,
                    'object' => $id,
                    'specific' => 0,
                    'amv' => $amv->id,
                    'asset' => $amv->asset->id,
                    'threat' => $amv->threat->id,
                    'vulnerability' => $amv->vulnerability->id,
                ];

                /** @var ObjectRiskService $objectRiskService */
                $objectRiskService = $this->get('objectRiskService');
                $objectRiskService->create($data);
            }
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

        $objects =  $this->get('table')->findByAnr($anrId);

        //retrieve objects categories
        $objectsCategoriesIds = [];
        foreach ($objects as $object) {
            $objectsCategoriesIds[$object['categoryId']] = $object['categoryId'];
        }

        if ($objectsCategoriesIds) {

            $rootCategories = $this->get('categoryTable')->getRootCategories($objectsCategoriesIds);

            foreach ($rootCategories as $key => $rootCategory) {
                if (!is_null($rootCategory['rootId'])) {
                    $rootCategories[$key] = $rootCategory['rootId'];
                } else {
                    unset($rootCategories[$key]);
                }
            }

            $categories = $this->get('categoryTable')->getByRootsOrIds($rootCategories, array_merge($objectsCategoriesIds, $rootCategories));
            foreach ($categories as $key => $category) {
                foreach ($objects as $object) {
                    if ($object['categoryId'] == $category['id']) {
                        $categories[$key]['objects'][] = $object;
                    }
                }
            }

            return $categories;
        } else {
            return [];
        }
    }

    public function export($data) {
        if (empty($data['password'])) {
            throw new \Exception('You must type in a password', 412);
        }

        $entity = $this->getEntity($data['id']);

        if (!$entity || $entity['type'] != $this->forceType) {
            throw new \Exception('Entity `id` not found.');
        }

        $output = json_encode($entity);
        return $this->encrypt($output);
    }

    protected function encrypt($data) {
        return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::SALT, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }

    protected function decrypt($data) {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::SALT, $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }
}