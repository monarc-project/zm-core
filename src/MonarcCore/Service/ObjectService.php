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
    protected $modelService;

    protected $assetTable;
    protected $categoryTable;
    protected $rolfTagTable;

    const BDC = 'bdc';
    const ANR = 'anr';

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
        $objectsObjects = $objectObjectService->getList($page, $limit, null, null);

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
    public function create($data, $context = self::BACK_OFFICE) {

        //position
        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;
        if (!array_key_exists('implicitPosition', $data)) {
            throw  new \Exception('implicitPosition is missing', 412);
        } else  if ($data['implicitPosition'] == 3) {
            if (!$previous) {
                throw  new \Exception('previous is missing', 412);
            }
        }
        $position = $this->managePositionCreation('category', $data['category'], (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;
        unset($data['implicitPosition']);

        //create object
        $object = $this->get('entity');
        $object->exchangeArray($data);

        //object dependencies
        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        if (array_key_exists('source', $data)) {
            $object->source = $this->get('table')->getEntity($data['source']);
        }

        //security
        if ($object->mode == self::IS_GENERIC && $object->asset->mode == self::IS_SPECIFIC) {
            throw new \Exception("You can't have a generic object based on a specific asset", 412);
        }
        if (array_key_exists('modelId', $data)) {
            $this->get('modelService')->canAcceptObject($data['modelId'], $object, $context);
        }

        if ($context == self::BACK_OFFICE) {
            //create object type bdc
            $object->type = self::BDC;
            $id = $this->get('table')->save($object);

            //attach object to anr
            if (array_key_exists('modelId', $data)) {

                $model = $this->get('modelService')->getEntity($data['modelId']);

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

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $objectId
     * @param $parentId
     * @param $position
     */
    public function instantiateObjectToAnr($anrId, $objectId, $parentId, $position) {

        if ($position == 0) {
            $position = 1;
        }
        $this->get('table')->instantiateObjectToAnr($anrId, $objectId, $parentId, $position);
    }


    /**
     * Attach object to Anr
     *
     * @param $object
     * @param $anrId
     * @param null $parent
     */
    public function attachObjectToAnr($object, $anrId, $parent = null) {

        $anrObject = clone $object;
        $anrObject->id = null;
        $anrObject->type = self::ANR;
        $anrObject->anr = $anrId;
        $anrObject->source = $this->get('table')->getEntity($object->id);

        $id = $this->get('table')->save($anrObject);

        if ($parent) {
            $data = [
                'anr' => $anrId,
                'father' => $parent,
                'child' => $id,
            ];
            $this->get('objectObjectService')->create($data);
        }

        //retrieve childs
        $childs = $this->get('objectObjectService')->getChilds($object->id);
        foreach ($childs as $child) {

            $childObject = $this->get('table')->getEntity($child['childId']);

            $this->attachObjectToAnr($childObject, $anrId, $id);

        }
    }
}