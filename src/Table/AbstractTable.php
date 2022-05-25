<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Monarc\Core\Model\Entity\AnrSuperClass;

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
     * @throws ORMException|OptimisticLockException
     */
    public function save(object $entity, bool $flushAll = true): void
    {
        $this->entityManager->persist($entity);
        if ($flushAll) {
            $this->entityManager->flush();
        }
    }

    /**
     * @throws ORMException|OptimisticLockException
     */
    public function remove(object $entity, bool $flushAll = true): void
    {
        $this->entityManager->remove($entity);
        if ($flushAll) {
            $this->entityManager->flush();
        }
    }

    /**
     * @throws ORMException|OptimisticLockException
     */
    public function removeList(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
        $this->entityManager->flush();
    }

    /**
     * @throws ORMException|OptimisticLockException
     */
    public function flush(): void
    {
        $this->entityManager->flush();
    }

    /**
     * Can search by int ID, str UUID or composite primary key like uuid + anr (e.g. ['uuid' => '', 'anr' => 1]).
     *
     * @param mixed $id
     * @param bool $throwErrorIfNotFound
     *
     * @return object|null
     */
    public function findById($id, bool $throwErrorIfNotFound = true): ?object
    {
        $entity = $this->getRepository()->find($id);
        if ($throwErrorIfNotFound && $entity === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), \is_array($id) ? $id : [$id]);
        }

        return $entity;
    }

    /**
     * Performs search by integer IDs list.
     *
     * @param int[] $ids
     *
     * @return object[]
     */
    public function findByIds(array $ids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');

        return $queryBuilder->where($queryBuilder->expr()->in('t.id', array_map('\intval', $ids)))
            ->getQuery()
            ->getResult();
    }

    /**
     * Seeks records by params with specific order and returns entities list.
     * @see AbstractTable::applyQueryParams()
     * @see AbstractTable::applyQueryOrder()
     *
     * @return object[]
     */
    public function findByParams(array $params, array $order = []): array
    {
        $tableAlias = 't';
        $queryBuilder = $this->getRepository()->createQueryBuilder($tableAlias);

        $this->applyQueryParams($queryBuilder, $params, $tableAlias)
            ->applyQueryOrder($queryBuilder, $order, $tableAlias);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Seeks records inside an analysis by params with specific order and returns entities list.
     * The param $anr is explicitly set here to prevent possible issues.
     *
     * @return object[]
     */
    public function findByAnrAndParams(AnrSuperClass $anr, array $params, array $order = []): array
    {
        $params['anr'] = $anr;

        return $this->findByParams($params, $order);
    }

    /**
     * Validates if the entity is new or already persisted.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isEntityPersisted(object $entity): bool
    {
        return $this->entityManager->contains($entity);
    }

    /**
     * Refreshes the entity data. Fetched again from the database. In case the data are changed by direct query.
     *
     * @param object $entity
     */
    public function refresh(object $entity): void
    {
        $this->entityManager->refresh($entity);
    }

    /**
     * The method is called from PositionUpdateTrait and can be used only for table classes that implement
     * PositionUpdatableTableInterface.
     *
     * @param int $positionFrom Starting position to update.
     * @param int $positionTo Latest position value to update (no limit if negative).
     * @param int $increment Increment or decrement (negative) value for the position shift.
     * @param array $params Used to pass additional filter column and value to be able to make the shift
     *                      within a specific range.
     */
    public function incrementPositions(int $positionFrom, int $positionTo, int $increment, array $params): void
    {
        $positionShift = $increment > 0 ? '+ ' . $increment : '- ' . abs($increment);
        $queryBuilder = $this->getRepository()->createQueryBuilder('t')
            ->update('t.position = t.position ' . $positionShift);

        foreach ($params as $fieldName => $fieldValue) {
            $queryBuilder->andWhere('t.' . $fieldName . ' = :' . $fieldName)
                ->setParameter($fieldName, $fieldValue);
        }

        $queryBuilder->andWhere('t.position >= :positionFrom')
            ->setParameter('positionFrom', $positionFrom);
        if ($positionTo > $positionFrom) {
            $queryBuilder->andWhere('t.position <= :positionTo')
                ->setParameter('positionTo', $positionTo);
        }

        $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws DbalException
     */
    public function beginTransaction(): void
    {
        $this->entityManager->getConnection()->beginTransaction();
    }

    /**
     * @throws ConnectionException|DbalException
     */
    public function commit(): void
    {
        $this->entityManager->getConnection()->commit();
    }

    /**
     * @throws ConnectionException|DbalException
     */
    public function rollback(): void
    {
        $this->entityManager->getConnection()->rollBack();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $params Expected format (default operand value is "OR"):
     *              [
     *                  'search' => [
     *                      'fields' => ['field1', 'relation.label'], 'string' => 'search str', 'operand' => 'OR|AND'
     *                  ],
     *                  'filter' => ['{field1}' => '{value1}', ...],
     *              ].
     * @param string $tableAlias Alias of applicable the field alias prefix.
     *                                If null, then expected to be set for each filed in params.
     *
     * @return $this
     *
     * @throws LogicException
     */
    protected function applyQueryParams(QueryBuilder $queryBuilder, array $params, string $tableAlias): self
    {
        if (!empty($params['search'])) {
            $this->validateQueryParamsFormat($params);

            $searchQuery = [];
            foreach ($params['search']['fields'] as $field) {
                $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias(
                    $queryBuilder,
                    $tableAlias,
                    $field
                );
                $searchQuery[] = ' ' . $fieldNameWithAlias . ' LIKE :searchString ';
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
                $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias($queryBuilder, $tableAlias, $field);
                $queryBuilder->andWhere($fieldNameWithAlias . ' = :value')
                    ->setParameter('value', $value);
            }
        }

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $order Expected format: ['fieldName' => 'ASC|DESC', ...].
     * @param string $tableAlias Alias of applicable the field alias prefix.
     *
     * @return $this
     */
    protected function applyQueryOrder(QueryBuilder $queryBuilder, array $order, string $tableAlias): self
    {
        foreach ($order as $field => $direction) {
            $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias($queryBuilder, $tableAlias, $field);
            $queryBuilder->addOrderBy($fieldNameWithAlias, $direction);
        }

        return $this;
    }

    private function linkRelationAndGetFiledNameWithAlias(
        QueryBuilder $queryBuilder,
        string $tableAlias,
        string $field
    ): string {
        if (strpos($field, '.') !== false) {
            $fieldParts = explode('.', $field);
            $relationField = current($fieldParts);
            $fieldNamePart = end($fieldParts);
            if (!\in_array($relationField, $queryBuilder->getAllAliases(), true)) {
                $joinString = $tableAlias === '' ? $relationField : $tableAlias . '.' . $relationField;
                $queryBuilder->innerJoin($joinString, $relationField);
            }

            return $relationField . '.' . $fieldNamePart;
        }

        return $tableAlias === '' ? $field : $tableAlias . '.' . $field;
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
