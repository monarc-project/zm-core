<?php
namespace MonarcCore\Model\Table;

class InstanceRiskTable extends AbstractEntityTable {

    public function getInstancesRisks($anrId, $instancesIds) {

        $qb = $this->getRepository()->createQueryBuilder('ir');

        return $qb
            ->select()
            ->where($qb->expr()->in('ir.instance', $instancesIds))
            ->andWhere('ir.anr = :anr ')
            ->setParameter(':anr', $anrId)
            ->getQuery()
            ->getResult();
    }

}
