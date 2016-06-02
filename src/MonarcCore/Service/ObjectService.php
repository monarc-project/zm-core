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

    protected $dependencies = ['asset', 'category', 'rolfTag'];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return array
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        $objectObjectService = $this->get('objectObjectService');

        $objectsObjects = $objectObjectService->getList($page = 1, $limit = 25, $order = null, $filter = null);

        $fatherList = [];
        $childList = [];
        $fatherObject = [];
        $childHierarchy = [];
        foreach($objectsObjects as $objectsObject) {
            $fatherList[] = $objectsObject['father']->id;
            $childList[] = $objectsObject['child']->id;
            $fatherObject[$objectsObject['father']->id] = $objectsObject['father'];
            $childHierarchy[] = [
                'father' => $objectsObject['father'],
                'child' => $objectsObject['child'],
            ];
        }
        $fatherList = array_unique($fatherList);
        $childList = array_unique($childList);

        $rootList = [];
        foreach($fatherList as $father) {
            if (! in_array($father, $childList)) {
                $rootList[] = $father;
            }
        }


        $hierarchy = [];
        foreach($rootList as $root) {
            $hierarchy[] = $this->recursiveChild($hierarchy, $fatherObject[$root], $childHierarchy);
        }

        return $hierarchy;

    }


    public function recursiveChild($hierarchy, $parent, &$childHierarchy) {


        $childs = [];
        foreach($childHierarchy as $key => $link) {
            $fatherId = (int) $link['father']->id;
            if ($fatherId == $parent->id) {

                $childs[] = $this->recursiveChild($hierarchy, $link['child'], $childHierarchy);
                unset($childHierarchy[$key]);
            }
        }


        $hierarchy = $parent->getJsonArray();
        $this->formatDependencies($hierarchy, $this->dependencies);
        unset($hierarchy['__initializer__']);
        unset($hierarchy['__cloner__']);
        unset($hierarchy['__isInitialized__']);
        if ($childs) {
            $hierarchy['childs'] = $childs;
        }

        return $hierarchy;
    }
}