<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Table;

/**
 * Class InstanceRiskOpTable
 * @package MonarcCore\Model\Table
 */
class InstanceRiskOpTable extends AbstractEntityTable
{
    /**
     * Get Instances Risks Op
     * @param $anrId
     * @param $instancesIds
     * @param array $params
     * @return array
     */
    public function getInstancesRisksOp($anrId, $instancesIds, $params = [])
    {
        $qb = $this->getRepository()->createQueryBuilder('iro');

        if (empty($instancesIds)) {
            $instancesIds[] = 0;
        }
        $return = $qb
            ->select()
            ->where($qb->expr()->in('iro.instance', $instancesIds))
            ->andWhere('iro.anr = :anr ')
            ->setParameter(':anr', $anrId);

        if (isset($params['kindOfMeasure'])) {
            $return->andWhere('iro.kindOfMeasure = :kim')
                ->setParameter(':kim', $params['kindOfMeasure']);
        }

        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $return->andWhere('iro.cacheNetRisk > :cnr')
                ->setParameter(':cnr', $params['thresholds']);
        }

        if (isset($params['keywords']) && !empty($params['keywords'])) {
            $anr = new \MonarcFO\Model\Entity\Anr();
            $anr->setDbAdapter($this->getDb());
            $anr->set('id', $anrId);
            $anr = $this->getDb()->fetch($anr);
            if (!$anr) {
                throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
            }
            $l = $anr->get('language');

            $fields = [
            'riskCacheLabel' .$l,
            'riskCacheDescription' . $l,
            'comment'];

            $query = [];
            foreach ($fields as $f) {
                $query[] = $qb->expr()->like('iro.' . $f, "'%" . $params['keywords'] . "%'");
            }
            $orX = $qb->expr()->orX();
            $orX->addMultiple($query);
            $return->andWhere($orX);
        }

        $params['order_direction'] = isset($params['order_direction']) && strtolower(trim($params['order_direction'])) != 'asc' ? 'DESC' : 'ASC';
        return $return->orderBy('iro.' . $params['order'], $params['order_direction'])
                      ->getQuery()
                      ->getResult();
    }
}
