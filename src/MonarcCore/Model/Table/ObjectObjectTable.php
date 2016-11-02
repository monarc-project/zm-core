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

    public function getDirectParentsInfos($child_id){
        return $this->getRepository()->createQueryBuilder('oo')
                    ->select(['o.name1', 'o.name2', 'o.name3', 'o.name4', 'o.label1', 'o.label2', 'o.label3', 'o.label4' ])
                    ->innerJoin('oo.father', 'o')
                    ->where('oo.child = :child_id')
                    ->setParameter(':child_id', $child_id)
                    ->getQuery()
                    ->getResult();
    }
}
