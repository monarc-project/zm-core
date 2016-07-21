<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Object;

/**
 * Object Service
 *
 * Class ObjectService
 * @package MonarcCore\Service
 */
class ObjectService extends AbstractService
{
    protected $objectObjectService;

    // Must be 16, 24 or 32 characters
    const SALT = '__$$00_C4535_5M1L3_00$$__XMP0)XW';

    protected $assetTable;
    protected $categoryTable;
    protected $rolfTagTable;

    protected $filterColumns = [
        'name1', 'name2', 'name3', 'name4',
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4'
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

    public function getEntity($id) {
        /** @var Object $entity */
        $entity = $this->get('table')->get($id);

        // Retrieve children recursively
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $entity['children'] = $objectObjectService->getRecursiveChildren($entity['id']);

        return $entity;
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
    public function create($data) {

        $entity = $this->get('entity');

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;

        $position = $this->managePositionCreation('category', $data['category'], (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;
        $data['type'] = $this->forceType;

        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $id = $this->get('table')->save($entity);

        if (array_key_exists('parent', $data)) {
            $objectObjectData = [
                'father' => (int) $data['parent'],
                'child' => (int) $id,
            ];

            $objectObjectService = $this->get('objectObjectService');
            $objectObjectService->create($objectObjectData);
        }

        return $id;
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id, $data){

        $entity = $this->get('table')->getEntity($id);
        if(!$entity || $entity->get('type') != $this->forceType){
            throw new \Exception('Entity `id` not found.');
            return false;
        }
        $entity->setLanguage($this->getLanguage());

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;

        if (array_key_exists('implicitPosition', $data)) {
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

        $entity['implicitPosition'] = array_key_exists('implicitPosition', $data) ? $data['implicitPosition'] : 2;

        return $this->create($entity);
    }

    public function export($data) {
        if (!array_key_exists('password', $data) || empty($data['password'])) {
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