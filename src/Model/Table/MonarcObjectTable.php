<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Entity\RolfTagSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class MonarcObjectTable
 * @package Monarc\Core\Model\Table
 */
class MonarcObjectTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, MonarcObject::class, $connectedUserService);
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function findByUuid(string $uuid): ObjectSuperClass
    {
        /** @var ObjectSuperClass $object */
        $object = $this->getRepository()->createQueryBuilder('o')
            ->where('o.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($object == null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$uuid]);
        }

        return $object;
    }

    /**
     * Get generic by asset id
     *
     * @param $assetId
     * @return array
     */
    public function getGenericByAssetId($assetId)
    {
        $objects = $this->getRepository()->createQueryBuilder('o')
            ->select(array('o.uuid'))
            ->where('o.asset = :assetId')
            ->andWhere('o.mode = :mode')
            ->setParameter(':assetId', $assetId)
            ->setParameter(':mode', 0)
            ->getQuery()
            ->getResult();

        return $objects;
    }

    public function hasObjectsUnderRootCategoryExcludeObject(
        ObjectCategorySuperClass $rootCategory,
        ObjectSuperClass $excludeObject = null
    ): bool {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('o')
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
        return $this->getRepository()
            ->createQueryBuilder('o')
            ->join('o.category', 'oc')
            ->where('oc.root = :rootCategory OR oc = :rootCategory')
            ->setParameter('rootCategory', $rootCategory)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ObjectSuperClass[]
     */
    public function getObjectsUnderParentCategory(ObjectCategorySuperClass $parentCategory): array
    {
        return $this->getRepository()
            ->createQueryBuilder('o')
            ->join('o.category', 'oc')
            ->where('oc.parent = :parentCategory OR oc = :parentCategory')
            ->setParameter('parentCategory', $parentCategory)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check In Anr
     *
     * @param $anrid
     * @param $id
     * @return bool
     */
    public function checkInAnr($anrid, $id)
    {
        $stmt = $this->getDb()->getEntityManager()->getConnection()->prepare(
            'SELECT id
             FROM   anrs_objects
             WHERE  anr_id = :anrid
             AND    object_id = :oid'
        );
        $count = $stmt->executeStatement([':anrid' => $anrid, ':oid' => $id]);

        return $count > 0;
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
     * @return ObjectSuperClass[]
     */
    public function findByAnrAndRolfTag(AnrSuperClass $anr, RolfTagSuperClass $rolfTag): array
    {
        return [];
    }

    public function saveEntity(ObjectSuperClass $monarcObject, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($monarcObject);
        if ($flushAll) {
            $em->flush();
        }
    }
}
