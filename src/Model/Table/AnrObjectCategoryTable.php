<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrObjectCategory;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class AnrObjectCategoryTable
 * @package Monarc\Core\Model\Table
 */
class AnrObjectCategoryTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, AnrObjectCategory::class, $connectedUserService);
    }

    /**
     * TODO: in order to specify return object type we need to create a superclass for AnrObjectCategory.
     *
     * @param AnrSuperClass $anr
     * @param ObjectCategorySuperClass $objectCategory
     *
     * @return AnrObjectCategory|null
     */
    public function findOneByAnrAndObjectCategory(AnrSuperClass $anr, ObjectCategorySuperClass $objectCategory)
    {
        $result = $this->getRepository()
            ->createQueryBuilder('aoc')
            ->where('aoc.anr = :anr')
            ->andWhere('aoc.category = :category')
            ->setParameter('anr', $anr)
            ->setParameter('category', $objectCategory)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $result[0] ?? null;
    }

    /**
     * @return AnrObjectCategory[]
     */
    public function findByAnrOrderedByPosititon(AnrSuperClass $anr)
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
    public function findByObjectCategory(ObjectCategorySuperClass $objectCategory)
    {
        return $this->getRepository()
            ->createQueryBuilder('aoc')
            ->where('aoc.category = :category')
            ->setParameter('category', $objectCategory)
            ->getQuery()
            ->getResult();
    }
}
