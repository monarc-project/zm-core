<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\Amv;
use Monarc\Core\Model\Entity\AmvSuperClass;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Entity\ThreatSuperClass;
use Monarc\Core\Model\Entity\VulnerabilitySuperClass;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;

class AmvTable extends AbstractTable implements PositionUpdatableTableInterface
{
    public function __construct(EntityManager $entityManager, string $entityName = Amv::class)
    {
        parent::__construct($entityManager, $entityName);
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
     * @return AmvSuperClass[]
     */
    public function findByAmv(
        ?AssetSuperClass $asset,
        ?ThreatSuperClass $threat,
        ?VulnerabilitySuperClass $vulnerability
    ): array {
        $queryBuilder = $this->getRepository()->createQueryBuilder('amv');

        if ($asset !== null) {
            $queryBuilder->innerJoin('amv.asset', 'asset')
                ->where('asset.uuid = :asset_uuid')
                ->setParameter('asset_uuid', $asset->getUuid());
        }
        if ($threat !== null) {
            $queryBuilder->innerJoin('amv.threat', 'threat')
                ->where('threat.uuid = :threat_uuid')
                ->setParameter('threat_uuid', $threat->getUuid());
        }
        if ($vulnerability !== null) {
            $queryBuilder->innerJoin('amv.vulnerability', 'vulnerability')
                ->where('vulnerability.uuid = :vulnerability_uuid')
                ->setParameter('vulnerability_uuid', $vulnerability->getUuid());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByUuid(string $uuid): Amv
    {
        $amv = $this->getRepository()
            ->createQueryBuilder('a')
            ->select('a', 'm')
            ->leftJoin('a.measures', 'm')
            ->where('a.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
        if ($amv === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$uuid]);
        }

        return $amv;
    }

    public function findByAmvItemsUuids(
        string $assetUuid,
        string $threatUuid,
        string $vulnerabilityUuid
    ): ?AmvSuperClass {
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

    public function findMaxPosition(array $params): int
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('amv')->select('MAX(amv.position)');

        foreach ($params as $fieldName => $fieldValue) {
            $queryBuilder
                ->andWhere('amv.' . $fieldName . ' = :' . $fieldName)
                ->setParameter($fieldName, $fieldValue);
        }

        return (int)$queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }
}
