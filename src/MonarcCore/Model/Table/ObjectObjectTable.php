<?php
namespace MonarcCore\Model\Table;

class ObjectObjectTable extends AbstractEntityTable {

    /**
     * Shift Position From Position
     *
     * @param $anrId
     * @param $fatherId
     * @param $position
     */
    public function shiftPositionFromPosition($anrId, $fatherId, $position) {

        $this->getRepository()->createQueryBuilder('o')
            ->update()
            ->set('o.position', 'o.position + 1')
            ->where('o.anr = :anrId')
            ->andWhere('t.father >= :fatherId')
            ->andWhere('t.position >= :position')
            ->setParameter(':anrId', $anrId)
            ->setParameter(':fatherId', $fatherId)
            ->setParameter(':position', $position)
            ->getQuery()
            ->getResult();
    }

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
