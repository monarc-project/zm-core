<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Table\AnrObjectCategoryTable;

/**
 * Object Category Service
 *
 * Class ObjectCategoryService
 * @package MonarcCore\Service
 */
class ObjectCategoryService extends AbstractService
{
    protected $anrObjectCategoryTable;

    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id){
        $entity = $this->get('table')->get($id);

        $entity['previous'] = null;
        if($entity['position'] == 1){
            $entity['implicitPosition'] = 1;
        }else{
            $pos = $this->get('table')->getRepository()->createQueryBuilder('t')->select('count(t.id)');
            if(empty($entity['parent'])){
                $pos = $pos->where('t.parent IS NULL');
            }else{
                $pos = $pos->where('t.parent = :parent')
                    ->setParameter(':parent', $entity['parent']->id);
            }
                
            $pos = $pos->getQuery()->getSingleScalarResult();
            if($entity['position'] >= $pos){
                $entity['implicitPosition'] = 2;
            }else{
                $entity['implicitPosition'] = 3;
                // Autre chose ?te
                $prev = $this->get('table')->getRepository()->createQueryBuilder('t')->select('t.id');
                if(empty($entity['parent'])){
                    $prev = $prev->where('t.parent IS NULL');
                }else{
                    $prev = $prev->where('t.parent = :parent')
                        ->setParameter(':parent', $entity['parent']->id);
                }
                $prev = $prev->andWhere('t.position = :pos')
                    ->setParameter(':pos',$entity['position']-1)
                    ->getQuery()->getSingleScalarResult();
                $entity['previous'] = $prev;
            }
        }

        return $entity;
    }

    /**
     * Get List Specific
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param int $parentId
     * @return mixed
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $parentId = 0){
        if ($parentId <= 0) {
            $objects = $this->getList($page, $limit, $order, $filter);
        } else {
            $filterAnd = ['parent' => $parentId];

            $objects = $this->get('table')->fetchAllFiltered(
                array_keys($this->get('entity')->getJsonArray()),
                $page,
                $limit,
                $this->parseFrontendOrder($order),
                $this->parseFrontendFilter($filter, $this->filterColumns),
                $filterAnd
            );
        }


        $currentObjectsListId = [];
        foreach($objects as $object) {
            $currentObjectsListId[] = $object['id'];
        }

        //retrieve parent
        foreach($objects as $object) {
            $this->addParent($objects, $object, $currentObjectsListId);
        }

        return $objects;
    }

    /**
     * Add parent
     *
     * @param $objects
     * @param $object
     * @param $currentObjectsListId
     */
    protected function addParent(&$objects, $object, &$currentObjectsListId) {
        if ($object['parent']) {
            if (!in_array($object['parent']->id, $currentObjectsListId)) {

                $parent = $object['parent']->getJsonArray();
                unset($parent['__initializer__']);
                unset($parent['__cloner__']);
                unset($parent['__isInitialized__']);

                $objects[] = $parent;

                $currentObjectsListId[] = $object['parent']->id;

                $this->addParent($objects, $parent, $currentObjectsListId);
            }
        }
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     * @throws \Exception
     */
    public function create($data, $last = true) {

        $entity = $this->get('entity');

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        $parent = (isset($data['parent'])) ? $data['parent'] : null;

        if (empty($data['implicitPosition'])) {
            throw new \Exception("You must select a position for your category", 412);
        }

        $position = $this->managePositionCreation('parent', $parent, (int) $data['implicitPosition'], $previous);
        $data['position'] = $position;

        $entity->exchangeArray($data);

        //parent and root
        $parentValue = $entity->get('parent');
        if (!empty($parentValue)) {
            $parentEntity = $this->get('table')->getEntity($parentValue);
            $entity->setParent($parentEntity);

            $rootEntity = $this->getRoot($entity);
            $entity->setRoot($rootEntity);
        } else {
            $entity->setParent(null);
            $entity->setRoot(null);
        }

        $this->get('table')->save($entity);
        return $entity->getJsonArray();

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

        $previous = (isset($data['previous'])) ? $data['previous'] : null;
        $parent = (isset($data['parent'])) ? $data['parent'] : null;

        if (isset($data['implicitPosition'])) {
            $data['position'] = $this->managePosition('parent', $entity, $parent, $data['implicitPosition'], $previous, 'update', false, 1);
        }

        $entity->exchangeArray($data);

        //parent and root
        $parentValue = $entity->get('parent');
        if (!empty($parentValue)) {
            $parentEntity = $this->get('table')->getEntity($parentValue);
            $entity->setParent($parentEntity);

            $rootEntity = $this->getRoot($entity);
            $entity->setRoot($rootEntity);
        } else {
            $entity->setParent(null);
            $entity->setRoot(null);
        }

        if (empty($data['parent'])) {
            $entity->setParent(null);
            $entity->setRoot(null);
        }

        $this->get('table')->save($entity);
        return $entity->getJsonArray();
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        $entity = $this->getEntity($id);

        if ($entity['parent']) {
            $objectParentId = $entity['parent']->id;
        } else {
            $objectParentId = null;
        }
        $position = $entity['position'];

        $this->get('table')->changePositionsByParent('parent', $objectParentId, $position, 'down', 'after');

        $this->get('table')->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.parent', ':parentset')
            ->where('t.parent = :parentwhere')
            ->setParameter(':parentset', null)
            ->setParameter(':parentwhere', $id)
            ->getQuery()
            ->getResult();

        $this->get('table')->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.root', ':rootset')
            ->where('t.root = :rootwhere')
            ->setParameter(':rootset', null)
            ->setParameter(':rootwhere', $id)
            ->getQuery()
            ->getResult();

        $this->get('table')->delete($id);
    }

    public function patchLibraryCategory($categoryId, $data) {

        $anrId = $data['anr'];

        if (!isset($data['position'])) {
            throw new \Exception('Position missing', 412);
        }

        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategory = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'category' => $categoryId])[0];

        if ($data['position'] != $anrObjectCategory->position) {

            $previousAnrObjectCategoryPosition = ($data['position'] > $anrObjectCategory->position) ? $data['position'] : $data['position'] - 1;
            $previousAnrObjectCategory = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'position' => $previousAnrObjectCategoryPosition]);
            if ($previousAnrObjectCategory) {
                $implicitPosition = 3;
                $previous = $previousAnrObjectCategory[0];
            } else {
                $implicitPosition = 1;
                $previous = null;
            }

            $anrObjectCategory->position = $this->managePosition('anr', $anrObjectCategory, $anrId, $implicitPosition, $previous, 'update', $anrObjectCategoryTable);

            return $this->get('table')->save($anrObjectCategory);
        }
    }
}