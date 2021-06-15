<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

abstract class AbstractTable
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var string */
    private $entityName;

    public function __construct(EntityManager $entityManager, string $entityName)
    {
        $this->entityManager = $entityManager;
        $this->entityName = $entityName;
    }

    public function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository($this->entityName);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(object $entity, bool $flushAll = true): void
    {
        $this->entityManager->persist($entity);
        if ($flushAll) {
            $this->entityManager->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(object $entity, bool $flushAll = true): void
    {
        $this->entityManager->remove($entity);
        if ($flushAll) {
            $this->entityManager->flush();
        }
    }

    public function findById(int $id): ?object
    {
        return $this->entityManager->find($this->entityName, $id);
    }

    public function beginTransaction(): void
    {
        $this->entityManager->getConnection()->beginTransaction();
    }

    /**
     * @throws ConnectionException
     */
    public function commit(): void
    {
        $this->entityManager->getConnection()->commit();
    }

    /**
     * @throws ConnectionException
     */
    public function rollback(): void
    {
        $this->entityManager->getConnection()->rollBack();
    }
}
