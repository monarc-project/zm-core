<?php
namespace MonarcCore\Model\Table;

class InstanceRiskOpTable extends AbstractEntityTable {

    public function getInstancesRisksOp($anrId, $instancesIds, $params = []) {

        $qb = $this->getRepository()->createQueryBuilder('iro');

        if(empty($instancesIds)){
            $instancesIds[] = 0;
        }
        $return = $qb
            ->select()
            ->where($qb->expr()->in('iro.instance', $instancesIds))
            ->andWhere('iro.anr = :anr ')
            ->setParameter(':anr', $anrId);

        if (isset($params['kindOfMeasure'])) {
            $return->andWhere('iro.kindOfMeasure = :kim')
                ->setParameter(':kim',$params['kindOfMeasure']);
        }

        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $return->andWhere('iro.cacheNetRisk < :cnr')
                ->setParameter(':cnr',$params['thresholds']);
        }

        if (isset($params['keywords']) && !empty($params['keywords'])) {
            $fields = ['riskCacheLabel1', 'riskCacheLabel2', 'riskCacheLabel3', 'riskCacheLabel4', 'riskCacheDescription1', 'riskCacheDescription2', 'riskCacheDescription3', 'riskCacheDescription4', 'comment'];
            $query = [];
            foreach($fields as $f){
                $query[] = $qb->expr()->contains('iro.'.$f,$params['keywords']);
            }
            $return->andWhere($qb->expr()->orX($query));
        }

        return $return->orderBy('iro.cacheNetRisk','DESC')
            ->getQuery()
            ->getResult();
    }
}
