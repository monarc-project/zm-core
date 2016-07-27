<?php
namespace MonarcCore\Model\Table;

class InstanceTable extends AbstractEntityTable {

    /**
     * Create instance to anr
     *
     * @param $instance
     * @param $anrId
     * @param $parentId
     * @param $position
     * @throws Exception
     */
    public function createInstanceToAnr($instance, $anrId, $parentId, $position) {

        $this->getDb()->beginTransaction();

        try {
            //modify position of instances after position
            $this->shiftPositionFromPosition($anrId, $parentId, $position);

            //create instance
            $this->save($instance);

            $this->getDb()->commit();
        } catch (Exception $e) {
            $this->getDb()->rollBack();
            throw $e;
        }
    }

    /**
     * Shift Position From Position
     *
     * @param $anrId
     * @param $parentId
     * @param $position
     */
    public function shiftPositionFromPosition($anrId, $parentId, $position) {

        $this->getRepository()->createQueryBuilder('i')
            ->update()
            ->set('i.position', 'i.position + 1')
            ->where('i.anr = :anrId')
            ->andWhere('i.parent = :parentId')
            ->andWhere('i.position >= :position')
            ->setParameter(':anrId', $anrId)
            ->setParameter(':parentId', $parentId)
            ->setParameter(':position', $position)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find By Anr
     *
     * @param $anrId
     * @return array
     */
    public function findByAnr($anrId) {

        return $this->getRepository()->createQueryBuilder('i')
            ->select(array(
                'i.id', 'i.level', 'IDENTITY(i.parent) as parentId',
                'i.c', 'i.i', 'i.d', 'i.ch', 'i.ih', 'i.dh',
                'i.name1', 'i.name2', 'i.name3', 'i.name4',
                'i.label1', 'i.label2', 'i.label3', 'i.label4'
            ))
            ->where('i.anr = :anrId')
            ->setParameter(':anrId', $anrId)
            ->orderBy('i.parent', 'ASC')
            ->orderBy('i.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
