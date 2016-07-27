<?php
namespace MonarcCore\Service;

/**
 * Object Category Service
 *
 * Class ObjectCategoryService
 * @package MonarcCore\Service
 */
class ObjectCategoryService extends AbstractService
{
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $parentId = 0){
        if ($parentId <= 0) {
            return $this->getList($page, $limit, $order, $filter);
        } else {
            $filterAnd = ['parent' => $parentId];

            return $this->get('table')->fetchAllFiltered(
                array_keys($this->get('entity')->getJsonArray()),
                $page,
                $limit,
                $this->parseFrontendOrder($order),
                $this->parseFrontendFilter($filter, $this->filterColumns),
                $filterAnd
            );
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
            $data['position'] = $this->managePositionUpdate('parent', $entity, $parent, $data['implicitPosition'], $previous);
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