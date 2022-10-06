<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Entity\RolfTagSuperClass;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class MonarcObjectTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = MonarcObject::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function hasGenericObjectsWithAsset(AssetSuperClass $asset): bool
    {
        return (bool)$this->getRepository()->createQueryBuilder('o')
            ->select('COUNT(o.uuid)')
            ->where('o.asset = :asset')
            ->andWhere('o.mode = ' . ObjectSuperClass::MODE_GENERIC)
            ->setParameter(':asset', $asset)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasObjectsUnderRootCategoryExcludeObject(
        ObjectCategorySuperClass $rootCategory,
        ObjectSuperClass $excludeObject = null
    ): bool {
        $queryBuilder = $this->getRepository()->createQueryBuilder('o')
            ->select('COUNT(o.uuid)')
            ->join('o.category', 'oc', Expr\Join::WITH, 'o.category = oc')
            ->where('oc.root = :rootCategory OR oc = :rootCategory')
            ->setParameter('rootCategory', $rootCategory);
        if ($excludeObject !== null) {
            $queryBuilder->andWhere('o <> :excludeObject')
                ->setParameter('excludeObject', $excludeObject);
        }

        return (bool)$queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return ObjectSuperClass[]
     */
    public function getObjectsUnderRootCategory(ObjectCategorySuperClass $rootCategory): array
    {
        return $this->getRepository()->createQueryBuilder('o')
            ->join('o.category', 'oc')
            ->where('oc.root = :rootCategory OR oc = :rootCategory')
            ->setParameter('rootCategory', $rootCategory)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ObjectSuperClass[]
     */
    public function findByRolfTag(RolfTagSuperClass $rolfTag): array
    {
        return $this->getRepository()
            ->createQueryBuilder('o')
            ->where('o.rolfTag = :rolfTag')
            ->setParameter('rolfTag', $rolfTag)
            ->getQuery()
            ->getResult();
    }

    /**
     * TODO: should be dropped.
     *
     * @return ObjectSuperClass[]
     */
    public function findByAnrAndRolfTag(AnrSuperClass $anr, RolfTagSuperClass $rolfTag): array
    {
        return [];
    }
}
