<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */
namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class AnrTable
 * @package Monarc\Core\Model\Table
 */
class AnrTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Anr::class, $connectedUserService);
    }

    /**
     * TODO: can be removed after move...
     * @return AnrSuperClass[]
     */
    public function findByIds(array $ids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('a');

        return $queryBuilder->where($queryBuilder->expr()->in('a.id', array_map('\intval', $ids)))
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id): AnrSuperClass
    {
        /** @var Anr|null $anr */
        $anr = $this->getRepository()->find($id);
        if ($anr === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $anr;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveEntity(AnrSuperClass $anr, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($anr);
        if ($flushAll) {
            $em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(AnrSuperClass $anr, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($anr);
        if ($flushAll) {
            $em->flush();
        }
    }
}
