<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;

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
            throw EntityNotFoundException::fromClassNameAndIdentifier($this->entityName, \is_array($id) ? $id : [$id]);
        }

        return $entity;
    }

    public function findByUuid(string $uuid): object
    {
        return $this->findById($uuid);
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

    public function findByUuidAndAnr(string $uuid, AnrSuperClass $anr, bool $throwErrorIfNotFound = true): ?object
    {
        $entity = $this->getRepository()->createQueryBuilder('t')
            ->where('t.uuid = :uuid')
            ->andWhere('t.anr = :anr')
            ->setParameter('uuid', $uuid)
            ->setParameter('anr', $anr)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($throwErrorIfNotFound && $entity === null) {
            throw new EntityNotFoundException(sprintf(
                'Entity of type "%s", with ID %s was not found in analysis ID %d',
                $this->entityName,
                $uuid,
                $anr->getId()
            ));
        }

        return $entity;
    }

    public function findByIdAndAnr(int $id, AnrSuperClass $anr, bool $throwErrorIfNotFound = true): ?object
    {
         $entity = $this->getRepository()->createQueryBuilder('t')
             ->where('t.id = :id')
             ->andWhere('t.anr = :anr')
             ->setParameter('id', $id)
             ->setParameter('anr', $anr)
             ->setMaxResults(1)
             ->getQuery()
             ->getOneOrNullResult();
        if ($throwErrorIfNotFound && $entity === null) {
            throw new EntityNotFoundException(sprintf(
                'Entity of type "%s", with ID %d was not found in analysis ID %d',
                $this->entityName,
                $id,
                $anr->getId()
            ));
        }

        return $entity;
    }

    /**
     * @param string[] $uuids
     *
     * @return object[]
     */
    public function findByUuids(array $uuids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('t.uuid', $uuids))
            ->getQuery()
            ->getResult();
    }

    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Seeks records by params with specific order and returns entities list.
     * @see AbstractTable::applyQueryParams()
     * @see AbstractTable::applyQueryOrder()
     * @see AbstractTable::applyPaginationParams()
     *
     * @return object[]
     */
    public function findByParams(FormattedInputParams $params): array
    {
        $tableAlias = 't';
        $queryBuilder = $this->getRepository()->createQueryBuilder($tableAlias);

        $this->applyQueryParams($queryBuilder, $params, $tableAlias)
            ->applyQueryOrder($queryBuilder, $params->getOrder(), $tableAlias)
            ->applyPaginationParams($queryBuilder, $params);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param FormattedInputParams $params
     * @param string $countableField Is used to count by the field in the main table.
     *                               For normal use there are 2 options: 'id' or 'uuid'.
     *
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countByParams(FormattedInputParams $params, string $countableField = '*'): int
    {
        $tableAlias = 't';
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder($tableAlias)
            ->select('COUNT(' . ($countableField === '*' ? $tableAlias : $tableAlias . '.' . $countableField) . ')');

        $this->applyQueryParams($queryBuilder, $params, $tableAlias);

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
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
    public function incrementPositions(
        int $positionFrom,
        int $positionTo,
        int $increment,
        array $params,
        string $updater
    ): void {
        if (!($this instanceof PositionUpdatableTableInterface)) {
            return;
        }

        $positionShift = $increment > 0 ? '+ ' . $increment : '- ' . abs($increment);
        $queryBuilder = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position ' . $positionShift)
            ->set('t.updater', ':updater')
            ->setParameter('updater', $updater);

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
     * @param FormattedInputParams $params Expected format of filter and search:
     *      search: [
     *          'fields' => ['field1', 'relation.label'], 'string' => 'search str', 'operator' => 'OR|AND'
     *      ],
     *      filter: ['field1' => 'value1', 'field2.label => 'value2'].
     * @param string $tableAlias Alias of applicable the field alias prefix.
     *                                If null, then expected to be set for each filed in params.
     *
     * @return $this
     *
     * @throws LogicException
     */
    protected function applyQueryParams(
        QueryBuilder $queryBuilder,
        FormattedInputParams $params,
        string $tableAlias
    ): self {
        if ($params->hasSearch()) {
            $searchParams = $params->getSearch();
            $this->validateSearchParams($searchParams);

            $searchQuery = [];
            foreach ($searchParams['fields'] as $field) {
                $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias(
                    $queryBuilder,
                    $tableAlias,
                    $field
                );
                $searchQuery[] = ' ' . $fieldNameWithAlias . ' LIKE :searchString ';
            }
            $logicalOperator = 'OR';
            if (isset($searchParams['logicalOperator'])
                && \in_array($searchParams['logicalOperator'], ['OR', 'AND'], true)
            ) {
                $logicalOperator = $searchParams['logicalOperator'];
            }

            $queryBuilder->andWhere(implode($logicalOperator, $searchQuery))
                ->setParameter('searchString', '%' . $searchParams['string'] . '%');
        }

        if ($params->hasFilter()) {
            foreach ($params->getFilter() as $field => $filterParams) {
                $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias($queryBuilder, $tableAlias, $field);
                $operator = $filterParams['operator'] ?? Comparison::EQ;
                $fieldValueName = strpos($field, '.') !== false ? explode('.', $field)[0] : $field;
                $queryBuilder->andWhere($fieldNameWithAlias . ' ' . $operator . ' :' . $fieldValueName)
                    ->setParameter($fieldValueName, $filterParams['value']);
            }
        }

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $order Expected format: ['fieldName' => 'ASC|DESC', ...].
     * @param string $tableAlias Alias of applicable the field alias prefix.
     *
     * @return self
     */
    protected function applyQueryOrder(QueryBuilder $queryBuilder, array $order, string $tableAlias): self
    {
        foreach ($order as $field => $direction) {
            $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias($queryBuilder, $tableAlias, $field);
            $queryBuilder->addOrderBy($fieldNameWithAlias, $direction);
        }

        return $this;
    }

    protected function applyPaginationParams(QueryBuilder $queryBuilder, FormattedInputParams $params): self
    {
        $page = $params->getPage();
        $limit = $params->getLimit();
        $firstResult = $page > 0 ? ($page - 1) * $limit : 0;

        $queryBuilder->setFirstResult($firstResult);
        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
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
    private function validateSearchParams(array $searchParams): void
    {
        if (empty($searchParams['fields'])) {
            throw new LogicException('Search criteria should be applied to field(s).', 412);
        }
        if (empty($searchParams['string'])) {
            throw new LogicException('Search string should be provided to execute the query.', 412);
        }
    }
}
