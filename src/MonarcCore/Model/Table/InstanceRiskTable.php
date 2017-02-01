<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class InstanceRiskTable
 * @package MonarcCore\Model\Table
 */
class InstanceRiskTable extends AbstractEntityTable
{
    /**
     * Get Instances Risks
     *
     * @param $anrId
     * @param $instancesIds
     * @return array
     */
    public function getInstancesRisks($anrId, $instancesIds)
    {
        $qb = $this->getRepository()->createQueryBuilder('ir');

        if (empty($instancesIds)) {
            $instancesIds[] = 0;
        }

        return $qb
            ->select()
            ->where($qb->expr()->in('ir.instance', $instancesIds))
            ->andWhere('ir.anr = :anr ')
            ->setParameter(':anr', $anrId)
            ->getQuery()
            ->getResult();
    }
}