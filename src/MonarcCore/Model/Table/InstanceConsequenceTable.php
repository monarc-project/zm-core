<?php
namespace MonarcCore\Model\Table;

class InstanceConsequenceTable extends AbstractEntityTable {

    public function getInstancesConsequences($anrId, $scalesImpactTypesIds) {

        $qb = $this->getRepository()->createQueryBuilder('ic');

        if(empty($scalesImpactTypesIds)){
            $scalesImpactTypesIds[] = 0;
        }

        return $qb
            ->select()
            ->where($qb->expr()->in('ic.scaleImpactType', $scalesImpactTypesIds))
            ->andWhere('ic.anr = :anr ')
            ->setParameter(':anr', $anrId)
            ->getQuery()
            ->getResult();
    }
}
