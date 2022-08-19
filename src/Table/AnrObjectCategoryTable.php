<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrObjectCategory;
use Monarc\Core\Model\Entity\AnrObjectCategorySuperClass;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;

class AnrObjectCategoryTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = AnrObjectCategorySuperClass::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findOneByAnrAndObjectCategory(
        AnrSuperClass $anr,
        ObjectCategorySuperClass $objectCategory
    ): ?AnrObjectCategorySuperClass {
        return $this->getRepository()
            ->createQueryBuilder('aoc')
            ->where('aoc.anr = :anr')
            ->andWhere('aoc.category = :category')
            ->setParameter('anr', $anr)
            ->setParameter('category', $objectCategory)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return AnrObjectCategory[]
     */
    public function findByAnrOrderedByPosition(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('aoc')
            ->innerJoin('aoc.category', 'cat')
            ->where('aoc.anr = :anr')
            ->setParameter('anr', $anr)
            ->orderBy('cat.position')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AnrObjectCategory[]
     */
    public function findByObjectCategory(ObjectCategorySuperClass $objectCategory): array
    {
        return $this->getRepository()
            ->createQueryBuilder('aoc')
            ->where('aoc.category = :category')
            ->setParameter('category', $objectCategory)
            ->getQuery()
            ->getResult();
    }
}
