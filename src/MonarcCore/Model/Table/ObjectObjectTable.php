<?php
namespace MonarcCore\Model\Table;

class ObjectObjectTable extends AbstractEntityTable {

    /**
     * Get Childs
     *
     * @param $objectId
     * @return array
     */
    public function getChilds($objectId) {
        $child = $this->getRepository()->createQueryBuilder('o')
            ->select(array('IDENTITY(o.child) as childId'))
            ->where('o.father = :father')
            ->setParameter(':father', $objectId)
            ->getQuery()
            ->getResult();

        return $child;
    }
}
