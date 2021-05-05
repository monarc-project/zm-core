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
use Monarc\Core\Model\Entity\AssetSuperClass;
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
     * @return AmvSuperClass[]
     */
    public function findByAsset(AssetSuperClass $asset)
    {
        return $this->getRepository()->createQueryBuilder('amv')
            ->where('amv.asset = :asset')
            ->setParameter('asset', $asset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $asset
     * @param $threat
     * @param $vulnerability
     * @return AmvSuperClass[]
     */
    public function findByAMV($asset, $threat, $vulnerability)
    {
        $parameters = [];
        if (!is_null($asset)) {
            $parameters['asset'] = $asset->getUuid();
        }
        if (!is_null($threat)) {
            $parameters['threat'] = $threat->getUuid();
        }
        if (!is_null($vulnerability)) {
            $parameters['vulnerability'] = $vulnerability->getUuid();
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

    public function findByAmvItemsUuidAndAnrId(
        string $assetUuid,
        string $threatUuid,
        string $vulnerabilityUuid,
        ?int $anrId = null
    ): ?AmvSuperClass {
        $queryBuilder = $this->getRepository()->createQueryBuilder('amv');

        if ($anrId !== null) {
            $queryBuilder->andWhere('amv.anr = :anrId')->setParameter('anrId', $anrId);
        }

        return $queryBuilder
            ->innerJoin('amv.asset', 'a')
            ->innerJoin('amv.threat', 't')
            ->innerJoin('amv.vulnerability', 'v')
            ->andWhere('a.uuid = :asset_uuid')
            ->andWhere('t.uuid = :threat_uuid')
            ->andWhere('v.uuid = :vulnerability_uuid')
            ->setParameter('asset_uuid', $assetUuid)
            ->setParameter('threat_uuid', $threatUuid)
            ->setParameter('vulnerability_uuid', $vulnerabilityUuid)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
