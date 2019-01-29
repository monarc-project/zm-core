<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Table;

/**
 * Class AmvTable
 * @package MonarcCore\Model\Table
 */
class AmvTable extends AbstractEntityTable
{
    /**
     * Find By AMV
     *
     * @param $asset
     * @param $threat
     * @param $vulnerability
     * @return array
     */
    public function findByAMV($asset, $threat, $vulnerability)
    {
        $parameters = [];
        if (!is_null($asset)) {
            $parameters['asset'] = $asset->getId();
        }
        if (!is_null($threat)) {
            $parameters['threat'] = $threat->getId();
        }
        if (!is_null($vulnerability)) {
            $parameters['vulnerability'] = $vulnerability->getId();
        }

        $amvs = $this->getRepository()->createQueryBuilder('amv')
            ->select(array(
                'amv.id',
                'IDENTITY(amv.asset) as assetId',
                'IDENTITY(amv.threat) as threatId',
                'IDENTITY(amv.vulnerability) as vulnerabilityId'
            ));

        $first = true;
        foreach ($parameters as $parameter => $value) {
            if ($first) {
                $amvs->where('amv.' . $parameter . ' = :' . $parameter);
                $first = false;
            } else {
                $amvs->andWhere('amv.' . $parameter . ' = :' . $parameter);
            }
            $amvs->setParameter(':' . $parameter, $value);
        }

        return $amvs->getQuery()->getResult();
    }
}