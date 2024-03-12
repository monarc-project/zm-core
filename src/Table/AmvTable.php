<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\Amv;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\Threat;
use Monarc\Core\Model\Entity\Vulnerability;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class AmvTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = Amv::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return Amv[]
     */
    public function findByAsset(Asset $asset, array $orderBy = []): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('amv')
            ->where('amv.asset = :asset')
            ->setParameter('asset', $asset);

        foreach ($orderBy as $fieldName => $direction) {
            $queryBuilder->addOrderBy($fieldName, $direction);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByAssetAndPosition(Asset $asset, int $position): ?Amv
    {
        return $this->getRepository()->createQueryBuilder('amv')
            ->where('amv.asset = :asset')
            ->andWhere('amv.position = :position')
            ->setParameter('asset', $asset)
            ->setParameter('position', $position)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Amv[]
     */
    public function findByAmv(?Asset $asset, ?Threat $threat, ?Vulnerability $vulnerability): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('amv');

        if ($asset !== null) {
            $queryBuilder->innerJoin('amv.asset', 'asset')
                ->where('asset.uuid = :asset_uuid')
                ->setParameter('asset_uuid', $asset->getUuid());
        }
        if ($threat !== null) {
            $queryBuilder->innerJoin('amv.threat', 'threat')
                ->andWhere('threat.uuid = :threat_uuid')
                ->setParameter('threat_uuid', $threat->getUuid());
        }
        if ($vulnerability !== null) {
            $queryBuilder->innerJoin('amv.vulnerability', 'vulnerability')
                ->andWhere('vulnerability.uuid = :vulnerability_uuid')
                ->setParameter('vulnerability_uuid', $vulnerability->getUuid());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByAmvItemsUuids(string $assetUuid, string $threatUuid, string $vulnerabilityUuid): ?Amv
    {
        return $this->getRepository()->createQueryBuilder('amv')
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
