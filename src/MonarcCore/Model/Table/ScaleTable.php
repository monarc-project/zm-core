<?php
namespace MonarcCore\Model\Table;

class ScaleTable extends AbstractEntityTable {

    /**
     * Get By Anr and Type
     *
     * @param $anrId
     * @param $type
     * @return mixed
     * @throws \Exception
     */
    public function getByAnrAndType($anrId, $type) {

        $scales =  $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.id'))
            ->where('s.anr = :anrId')
            ->andWhere('s.type = :type')
            ->setParameter(':type', $type)
            ->setParameter(':anrId', $anrId)
            ->getQuery()
            ->getResult();

        if (! count($scales)) {
           throw new \Exception('Entity not exist', 422);
        } else {
            return $scales[0];
        }
    }
}
