<?php
namespace MonarcCore\Model\Table;

class ObjectCategoryTable extends AbstractEntityTable {

    /**
     * Get Child
     *
     * @param $id
     * @return array
     */
    public function getChild($id) {
        $child = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        return $child;
    }

    /**
     * Get root categories
     *
     * @param $objectsIds
     * @return array
     */
    public function getRootCategories($objectsIds) {

        $qb = $this->getRepository()->createQueryBuilder('t');

        return $qb
            ->select(array('IDENTITY(t.root) as rootId'))
            ->where($qb->expr()->in('t.id', $objectsIds))
            ->getQuery()
            ->getResult();
    }


    /**
     * Get By Roots Or Ids
     *
     * @param $rootIds
     * @param $ids
     * @return array
     */
    public function getByRootsOrIds($rootIds, $ids) {

        $fields = array('t.id', 't.label1', 't.label2', 't.label3', 't.label4', 't.position', 'IDENTITY(t.parent) as parentId');

        $qb = $this->getRepository()->createQueryBuilder('t');

        if (count($rootIds) && count($ids)) {
            $result = $qb
                ->select($fields)
                ->where($qb->expr()->in('t.id', $ids))
                ->orWhere($qb->expr()->in('t.root', $rootIds))
                ->getQuery()
                ->getResult();
        } else if (!count($rootIds) && count($ids)) {
            $result =  $qb
                ->select($fields)
                ->where($qb->expr()->in('t.id', $ids))
                ->getQuery()
                ->getResult();
        }

        return $result;
    }

}
