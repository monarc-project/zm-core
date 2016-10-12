<?php
namespace MonarcCore\Model\Table;

class InstanceRiskOpTable extends AbstractEntityTable {

    public function getInstancesRisksOp($anrId, $instancesIds) {

        $qb = $this->getRepository()->createQueryBuilder('iro');
        
        if(empty($instancesIds)){
            $instancesIds[] = 0;
        }

        return $qb
            ->select()
            ->where($qb->expr()->in('iro.instance', $instancesIds))
            ->andWhere('iro.anr = :anr ')
            ->setParameter(':anr', $anrId)
            ->getQuery()
            ->getResult();
    }
}
