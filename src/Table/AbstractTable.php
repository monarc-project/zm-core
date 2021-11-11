<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use LogicException;

abstract class AbstractTable
{
    protected EntityManager $entityManager;

    private string $entityName;

    public function __construct(EntityManager $entityManager, string $entityName)
    {
        $this->entityManager = $entityManager;
        $this->entityName = $entityName;
    }

    public function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository($this->entityName);
    }

    public function getEntityName(): string
    {
        return $this->entityName;
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

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->entityManager->flush();
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

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $params Expected format (default operand value is "OR"):
     *              [
     *                  'search' => ['fields' = ['{field1}', ...], 'string' = '{my search str}', 'operand' => 'OR|AND'],
     *                  'filter' => ['{field1}' => '{value1}', ...],
     *              ].
     * @param string|null $tableAlias Alias of applicable the field alias prefix.
     *                                If null, then expected to be set for each filed in params.
     *
     * @return $this
     *
     * @throws LogicException
     */
    protected function applyQueryParams(QueryBuilder $queryBuilder, array $params, string $tableAlias = null): self
    {
        $tableAliasPrefix = $tableAlias !== null ? $tableAlias . '.' : '';
        if (!empty($params['search'])) {
            $this->validateQueryParamsFormat($params);

            $searchQuery = [];
            foreach ($params['search']['fields'] as $fieldName) {
                $searchQuery[] = ' ' . $tableAliasPrefix . $fieldName . ' LIKE :searchString ';
            }
            $operand = 'OR';
            if (isset($params['search']['operand'])  && \in_array($params['search']['operand'], ['OR', 'AND'], true)) {
                $operand = $params['search']['operand'];
            }

            $queryBuilder->andWhere(implode($searchQuery, $operand))
                ->setParameter('searchString', '%' . $params['search']['string'] . '%');
        }

        if (!empty($params['filter'])) {
            foreach ($params['filter'] as $field => $value) {
                $queryBuilder->andWhere($tableAliasPrefix . $field . ' = :value')
                    ->setParameter('value', $value);
            }
        }

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $order Expected format: ['fieldName' => 'ASC|DESC', ...].
     * @param string|null $tableAlias Alias of applicable the field alias prefix.
     *
     * @return $this
     */
    protected function applyQueryOrder(QueryBuilder $queryBuilder, array $order, string $tableAlias = null): self
    {
        $tableAliasPrefix = $tableAlias !== null ? $tableAlias . '.' : '';
        foreach ($order as $field => $direction) {
            $queryBuilder->addOrderBy($tableAliasPrefix . $field, $direction);
        }

        return $this;
    }

    /**
     * @throws LogicException
     */
    private function validateQueryParamsFormat(array $params): void
    {
        if (isset($params['search']) && empty($params['search']['fields'])) {
            throw new LogicException('Search criteria should be applied to field(s).', 412);
        }
        if (isset($params['search']) && empty($params['search']['string'])) {
            throw new LogicException('Search string should be provided to execute the query.', 412);
        }
    }
}
