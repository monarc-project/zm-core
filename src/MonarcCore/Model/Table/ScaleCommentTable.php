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
            ->select(array('s.val', 'IDENTITY(s.scaleTypeImpact) as scaleTypeImpact', 's.comment1', 's.comment2', 's.comment3', 's.comment4'))
            ->where('s.scale = :scaleId')
            ->setParameter(':scaleId', $scaleId)
            ->getQuery()
            ->getResult();

        return $comments;
    }
}
