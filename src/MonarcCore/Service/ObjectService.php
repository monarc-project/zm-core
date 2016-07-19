<?php
namespace MonarcCore\Service;

/**
 * Object Service
 *
 * Class ObjectService
 * @package MonarcCore\Service
 */
class ObjectService extends AbstractService
{
    protected $objectObjectService;

    protected $assetTable;
    protected $categoryTable;
    protected $rolfTagTable;

    protected $filterColumns = [
        'name1', 'name2', 'name3', 'name4',
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4'
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

        //retrieve link father - child
        $objectObjectService = $this->get('objectObjectService');
        $objectsObjects = $objectObjectService->getList($page = 1, $limit = 25, null, null);

        //hierarchy
        $childHierarchy = [];
        foreach ($objectsObjects as $objectsObject) {
            if (!is_null($objectsObject['child'])) {
                if (array_key_exists($objectsObject['child']->id, $rootArray)) {
                    unset($rootArray[$objectsObject['child']->id]);
                }
            }

            $childHierarchy[] = [
                'id' => $objectsObject['id'],
                'father' => $objectsObject['father']->id,
                'child' => $objectsObject['child']->id,
            ];
        }

        $newRoot = [];
        foreach($rootArray as $value) {
            $newRoot[] = $value;
        }

        if ($lock == 'true') {
            return $newRoot;
        } else {

            //recursive
            $hierarchy = [];
            foreach ($newRoot as $root) {
                $hierarchy[] = $this->recursiveChild($hierarchy, $root['id'], $childHierarchy, $objectsArray);
            }

            return $hierarchy;
        }
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
}