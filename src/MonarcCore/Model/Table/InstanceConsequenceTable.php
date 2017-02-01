<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class InstanceConsequenceTable
 * @package MonarcCore\Model\Table
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