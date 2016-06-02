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
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4',];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
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

        if ((! array_key_exists('parent', $data)) || (is_null($data['parent']))) {
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

    /**
     * Get root
     *
     * @param $entity
     * @return mixed
     */
    public function getRoot($entity) {
        if (!is_null($entity->getParent())) {
            return $this->getRoot($entity->getParent());
        } else {
            return $entity;
        }
    }
}