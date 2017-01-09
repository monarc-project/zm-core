<?php
namespace MonarcCore\Model\Table;

class ScaleCommentTable extends AbstractEntityTable {

    /**
     * Get By Scale
     *
     * @param $scaleId
     * @return mixed
     * @throws \Exception
     */
    public function getByScale($scaleId) {

        $comments =  $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.val', 'IDENTITY(s.scaleImpactType) as scaleImpactType', 's.comment1', 's.comment2', 's.comment3', 's.comment4'))
            ->where('s.scale = :scaleId')
            ->setParameter(':scaleId', $scaleId)
            ->getQuery()
            ->getResult();

        return $comments;
    }

    public function getByScaleAndOutOfRange($scaleId, $min, $max) {
        $comments =  $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.id', 's.val', 'IDENTITY(s.scaleImpactType) as scaleImpactType', 's.comment1', 's.comment2', 's.comment3', 's.comment4'))
            ->where('s.scale = :scaleId AND (s.val > :max OR s.val < :min)')
            ->setParameter(':scaleId', $scaleId)
            ->setParameter(':min', $min)
            ->setParameter(':max', $max)
            ->getQuery()
            ->getResult();

        return $comments;
    }
}
