<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class ScaleTable
 * @package MonarcCore\Model\Table
 */
class ScaleTable extends AbstractEntityTable
{
    /**
     * Get By Anr and Type
     *
     * @param $anrId
     * @param $type
     * @return mixed
     * @throws \Exception
     */
    public function getByAnrAndType($anrId, $type)
    {
        $scales = $this->getRepository()->createQueryBuilder('s')
            ->select(array('s.id'))
            ->where('s.anr = :anrId')
            ->andWhere('s.type = :type')
            ->setParameter(':type', $type)
            ->setParameter(':anrId', $anrId)
            ->getQuery()
            ->getResult();

        if (!count($scales)) {
            throw new \Exception('Entity does not exist', 422);
        } else {
            return $scales[0];
        }
    }
}