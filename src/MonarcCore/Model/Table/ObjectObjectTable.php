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

    public function getDirectParentsInAnr($anrid, $id){
        $stmt = $this->getDb()->getEntityManager()->getConnection()->prepare(
         'SELECT         o.id, oo.id as linkid, o.label1, o.label2, o.label3, o.label4, o.name1, o.name2, o.name3, o.name4
          FROM           objects_objects oo
          INNER JOIN objects o
          ON                 oo.father_id = o.id
          INNER JOIN anrs_objects ao
          ON                 ao.object_id = o.id
          WHERE          ao.anr_id = :anrid
          AND                oo.child_id = :oid'
        );

        $stmt->execute([':anrid' => $anrid, ':oid' => $id]);
        return $stmt->fetchAll();
    }
}
