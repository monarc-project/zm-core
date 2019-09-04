<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

/**
 * Class InstanceConsequenceTable
 * @package Monarc\Core\Model\Table
 */
class InstanceConsequenceTable extends AbstractEntityTable
{
    /**
     * Get Instances Consequences
     *
     * @param $anrId
     * @param $scalesImpactTypesIds
     * @return array
     */
    public function getInstancesConsequences($anrId, $scalesImpactTypesIds)
    {
        $qb = $this->getRepository()->createQueryBuilder('ic');

        if (empty($scalesImpactTypesIds)) {
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
