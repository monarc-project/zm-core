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

    protected $filterColumns = ['category'];

    protected $dependencies = ['asset', 'category', 'rolfTag'];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param array $options
     * @return array
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $options = []){

        //retrieve all objects
        $objects = parent::getList($page = 1, $limit = 25, $order, $filter);
        $objectsArray = [];
        $rootArray = [];
        foreach($objects as $object) {
            $rootArray[$object['id']] = $object;
            $objectsArray[$object['id']] = $object;
        }

        //retrieve link father - child
        $objectObjectService = $this->get('objectObjectService');
        $objectsObjects = $objectObjectService->getList($page = 1, $limit = 25, $order, $filter);

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

        if ($options['lock'] == 'true') {
            return $rootArray;
        } else {

            //recursive
            $hierarchy = [];
            foreach ($rootArray as $root) {
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
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null){
        return count(parent::getList($page = 1, $limit = 25, $order, $filter));
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

        $position = $this->managePositionCreation($data['category'], (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;

        $entity->exchangeArray($data);

        foreach($this->dependencies as $dependency) {
            $value = $entity->get($dependency);
            if (!empty($value)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($value);
                $entity->$method($dependencyEntity);
            }
        }

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

        $previous = (array_key_exists('previous', $data)) ? $data['previous'] : null;

        if (array_key_exists('implicitPosition', $data)) {
            $data['position'] = $this->managePositionUpdate($entity, $data['category'], $data['implicitPosition'], $previous);
        }

        $entity->exchangeArray($data);

        foreach($this->dependencies as $dependency) {
            $value = $entity->get($dependency);
            if (!empty($value)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($value);
                $entity->$method($dependencyEntity);
            }
        }

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

        $this->get('table')->changePositionsByCategory($objectCategoryId, $position, 'down', 'after');

        $this->get('table')->delete($id);
    }


    /**
     * Manage position
     *
     * @param $objectCategoryId
     * @param $implicitPosition
     * @param null $previous
     * @return int
     */
    protected function managePositionCreation($objectCategoryId, $implicitPosition, $previous = null) {
        $position = 1;

        switch ($implicitPosition) {
            case 1:
                $this->get('table')->changePositionsByCategory($objectCategoryId, 1, 'up', 'after');
                $position = 1;
                break;
            case 2:
                $maxPosition = $this->get('table')->maxPositionByCategory($objectCategoryId);
                $position = $maxPosition + 1;
                break;
            case 3:
                $previousObject = $this->get('table')->getEntity($previous);
                $this->get('table')->changePositionsByCategory($objectCategoryId, $previousObject->position + 1, 'up', 'after');
                $position = $previousObject->position + 1;
                break;
        }

        return $position;
    }


    /**
     * Manage position update
     *
     * @param $object
     * @param $newObjectCategoryId
     * @param $implicitPosition
     * @param null $previous
     * @return int
     */
    protected function managePositionUpdate($object, $newObjectCategoryId, $implicitPosition, $previous = null) {

        $position = 1;

        if ($newObjectCategoryId == $object->category->id) {
            switch ($implicitPosition) {
                case 1:
                    $this->get('table')->changePositionsByCategory($object->category->id, $object->position, 'up', 'before');
                    $position = 1;
                    break;
                case 2:
                    $this->get('table')->changePositionsByCategory($object->category->id, $object->position, 'down', 'after');
                    $maxPosition = $this->get('table')->maxPositionByCategory($object->category->id);
                    $position = $maxPosition + 1;
                    break;
                case 3:
                    $previousObject = $this->get('table')->getEntity($previous);
                    if ($object->position < $previousObject->position) {
                        $this->get('table')->changePositionsByCategory($object->parent->id, $object->position, 'down', 'after');
                        $this->get('table')->changePositionsByCategory($object->parent->id, $previousObject->position, 'up', 'after');
                        $position = $previousObject->position;
                    } else {
                        $this->get('table')->changePositionsByCategory($object->parent->id, $previousObject->position, 'up', 'after', true);
                        $this->get('table')->changePositionsByCategory($object->parent->id, $object->position, 'down', 'after', true);
                        $position = $previousObject->position + 1;
                    }
                    break;
            }
        } else {
            $this->get('table')->changePositionsByCategory($object->category->id, $object->position, 'down', 'after');
            switch ($implicitPosition) {
                case 1:
                    $this->get('table')->changePositionsByCategory($newObjectCategoryId, 1, 'up', 'after');
                    $position = 1;
                    break;
                case 2:
                    $maxPosition = $this->get('table')->maxPositionByCategory($newObjectCategoryId);
                    $position = $maxPosition + 1;
                    break;
                case 3:
                    $previousObject = $this->get('table')->getEntity($previous);
                    $this->get('table')->changePositionsByCategory($newObjectCategoryId, $previousObject->position, 'up', 'after', true);
                    $position = $previousObject->position + 1;
                    break;
            }
        }

        return $position;
    }
}