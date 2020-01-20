<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\Amv;
use Monarc\Core\Model\Entity\AmvSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class AmvTable
 * @package Monarc\Core\Model\Table
 */
class AmvTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Amv::class, $connectedUserService);
    }

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
            $parameters['asset'] = is_string($asset->getUuid())?$asset->getUuid():$asset->getUuid()->toString();
        }
        if (!is_null($threat)) {
            $parameters['threat'] = is_string($threat->getUuid())?$threat->getUuid():$threat->getUuid()->toString();
        }
        if (!is_null($vulnerability)) {
            $parameters['vulnerability'] = is_string($vulnerability->getUuid())?$vulnerability->getUuid():$vulnerability->getUuid()->toString();
        }

        $amvs = $this->getRepository()->createQueryBuilder('amv')
            ->select(array(
                'amv.uuid',
                'asset.uuid as assetId',
                'threat.uuid as threatId',
                'vulnerability.uuid as vulnerabilityId'
            ));
        $amvs->innerJoin('amv.asset','asset')
              ->innerJoin('amv.threat','threat')
              ->innerJoin('amv.vulnerability','vulnerability');

        $first = true;
        foreach ($parameters as $parameter => $value) {
            if ($first) {
                $amvs->where( $parameter . '.uuid = :' . $parameter);
                $first = false;
            } else {
                $amvs->andWhere( $parameter . '.uuid = :' . $parameter);
            }
            $amvs->setParameter(':' . $parameter, $value);
        }

        return $amvs->getQuery()->getResult();
    }

    /**
     * @return AmvSuperClass[]
     */
    public function findByUuid(string $uuid)
    {
        return $this->getRepository()
            ->createQueryBuilder('a')
            ->select('a', 'm')
            ->leftJoin('a.measures', 'm')
            ->where('a.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getResult();
    }
}
