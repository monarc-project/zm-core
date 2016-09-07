<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Table\AnrTable;

/**
 * Object Category Service
 *
 * Class ObjectCategoryService
 * @package MonarcCore\Service
 */
class ObjectCategoryService extends AbstractService
{
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    protected $dependencies = ['anr'];

    protected $anrTable;

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
     * @throws \Exception
     */
    public function create($data) {

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

        return $this->get('table')->save($entity);

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
            $data['position'] = $this->managePosition('parent', $entity, $parent, $data['implicitPosition'], $previous, 'update');
        }

        if (isset($data['anr'])) {
            if ($data['anr']) {
                /** @var AnrTable $anrTable */
                $anrTable = $this->get('anrTable');
                $anr = $anrTable->getEntity($data['anr']);
                $data['anr'] = $anr;
            }
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

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        $entity = $this->getEntity($id);

        $objectParentId = $entity['parent']->id;
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
}