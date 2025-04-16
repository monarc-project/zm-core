<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Monarc\Core\Entity\AssetSuperClass;
use Monarc\Core\Entity\MonarcObject;
use Monarc\Core\Entity\ObjectCategorySuperClass;
use Monarc\Core\Entity\ObjectSuperClass;
use Monarc\Core\Entity\RolfTagSuperClass;

class MonarcObjectTable extends AbstractTable
{
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
            ->setParameter('asset', $asset)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasObjectsUnderRootCategoryExcludeObject(
        ObjectCategorySuperClass $rootCategory,
        ObjectSuperClass $excludeObject
    ): bool {
        return (bool)$this->getRepository()->createQueryBuilder('o')
            ->select('COUNT(o.uuid)')
            ->join('o.category', 'oc', Expr\Join::WITH, 'o.category = oc')
            ->where('oc.root = :rootCategory OR oc = :rootCategory')
            ->andWhere('o <> :excludeObject')
            ->setParameter('rootCategory', $rootCategory)
            ->setParameter('excludeObject', $excludeObject)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return ObjectSuperClass[]
     */
    public function findByRolfTag(RolfTagSuperClass $rolfTag): array
    {
        return $this->getRepository()->createQueryBuilder('o')
            ->where('o.rolfTag = :rolfTag')
            ->setParameter('rolfTag', $rolfTag)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MonarcObject[]
     */
    public function findGenericOrSpecificByModelIdsFilteredByNamePart(
        array $modelIds,
        string $namePart,
        int $languageIndex
    ): array {
        $queryBuilder = $this->getRepository()->createQueryBuilder('o')
            ->where('o.mode = ' . ObjectSuperClass::MODE_GENERIC);
        if (!empty($modelIds)) {
            $queryBuilder->distinct()
                ->leftJoin('o.anrs', 'anrs')
                ->innerJoin('anrs.model', 'm')
                ->orWhere($queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('o.mode', ObjectSuperClass::MODE_SPECIFIC),
                    $queryBuilder->expr()->in('m.id', array_map('\intval', $modelIds))
                ));
        }
        if ($namePart !== '') {
            $queryBuilder->andWhere('o.name' . $languageIndex . ' LIKE :name')
                ->setParameter('name', '%' . $namePart . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
