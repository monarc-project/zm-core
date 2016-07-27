<?php
namespace MonarcCore\Model\Table;

class ObjectObjectTable extends AbstractEntityTable {

    /**
     * Get Childs
     *
     * @param $objectId
     * @return array
     */
    public function getChildren($objectId) {
        $child = $this->getRepository()->createQueryBuilder('o')
            ->select(array('IDENTITY(o.child) as childId', 'o.position'))
            ->where('o.father = :father')
            ->setParameter(':father', $objectId)
            ->getQuery()
            ->getResult();

        return $child;
    }
}
