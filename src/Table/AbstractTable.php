<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\ParameterType;
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
use Monarc\Core\Entity\AnrSuperClass;

abstract class AbstractTable
{
    protected EntityManager $entityManager;

    private string $entityName;

    public function __construct(EntityManager $entityManager, string $entityName)
    {
        $this->entityManager = $entityManager;
        $this->entityName = $entityName;
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
     * Performs search by integer IDs list and analysis ID.
     *
     * @param int[] $ids
     *
     * @return object[]
     */
    public function findByIdsAndAnr(array $ids, AnrSuperClass $anr): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');

        return $queryBuilder->where($queryBuilder->expr()->in('t.id', array_map('\intval', $ids)))
            ->andWhere('t.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return object[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
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

    /**
     * @param string[] $uuids
     *
     * @return object[]
     */
    public function findByUuidsAndAnr(array $uuids, AnrSuperClass $anr): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('t.uuid', $uuids))
            ->andWhere('t.anr = :anr')
            ->setParameter('anr', $anr)
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
        $queryBuilder = $this->getRepository()->createQueryBuilder($tableAlias)
            ->select('COUNT(' . ($countableField === '*' ? $tableAlias : $tableAlias . '.' . $countableField) . ')');

        $this->applyQueryParams($queryBuilder, $params, $tableAlias);

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int)$this->getRepository()->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
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

    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->entityManager->getConnection()->quote($value, $type);
    }

    protected function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository($this->entityName);
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
            $this->applySearch($queryBuilder, $tableAlias, $params->getSearch());
        }

        if ($params->hasFilter()) {
            foreach ($params->getFilter() as $field => $filterParams) {
                if (!isset($filterParams['isUsedInQuery']) || $filterParams['isUsedInQuery'] === true) {
                    $this->applyFilter($queryBuilder, $tableAlias, $field, $filterParams);
                }
            }
        }

        return $this;
    }

    protected function applySearch(QueryBuilder $queryBuilder, string $tableAlias, array $searchParams): self
    {
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

        return $this;
    }

    protected function applyFilter(
        QueryBuilder $queryBuilder,
        string $tableAlias,
        string $field,
        array $filterParams
    ): self {
        $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias(
            $queryBuilder,
            $tableAlias,
            $field
        );
        $operator = $filterParams['operator'] ?? Comparison::EQ;
        if (\is_array($filterParams['value']) && !\in_array($operator, [Comparison::IN, Comparison::NIN], true)) {
            $operator = Comparison::IN;
        }

        /* Handle `is null` in the where clause. */
        if ($filterParams['value'] === null) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNull($fieldNameWithAlias));

            return $this;
        }

        $paramName = str_contains($field, '.') ? str_replace('.', '_', $field) : $field;
        $whereCondition = $fieldNameWithAlias . ' ' . $operator . ' :' . $paramName;
        if (\is_array($filterParams['value'])
            && \in_array($operator, [Comparison::IN, Comparison::NIN], true)
        ) {
            $whereCondition = $operator === Comparison::IN
                ? $queryBuilder->expr()->in($fieldNameWithAlias, ':' . $paramName)
                : $queryBuilder->expr()->notIn($fieldNameWithAlias, ':' . $paramName);
        }

        /* Used for the 2 fields relation to be able to add the anr property to the joining tables. */
        foreach ($filterParams['relationConditions'] ?? [] as $relationCondition) {
            $queryBuilder->andWhere($relationCondition);
        }

        $queryBuilder
            ->andWhere($whereCondition)
            ->setParameter($paramName, $filterParams['value']);

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $order Expected format: ['fieldName' => 'ASC|DESC', ...].
     *                      The symbol '|' at the end is used to define an SQL function.
     * @param string $tableAlias Alias of applicable the field alias prefix.
     *
     * @return self
     */
    protected function applyQueryOrder(QueryBuilder $queryBuilder, array $order, string $tableAlias): self
    {
        foreach ($order as $field => $direction) {
            foreach (str_contains($field, ',') ? explode(',', $field) : [$field] as $name) {
                $fieldNameWithAlias = $this->linkRelationAndGetFiledNameWithAlias($queryBuilder, $tableAlias, $name);
                if (str_contains($fieldNameWithAlias, '|')) {
                    $fieldNameWithAliasParts = explode('|', $fieldNameWithAlias);
                    $fieldNameWithAlias = current($fieldNameWithAliasParts);
                    $fieldNameWithAlias = end($fieldNameWithAliasParts) . '(' . $fieldNameWithAlias . ')';
                }
                $queryBuilder->addOrderBy($fieldNameWithAlias, $direction);
            }
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
        if (str_contains($field, '.')) {
            $fieldParts = explode('.', $field);
            $relationField = current($fieldParts);
            $fieldNamePart = next($fieldParts);
            if (!\in_array($relationField, $queryBuilder->getAllAliases(), true)) {
                $joinString = $tableAlias === '' ? $relationField : $tableAlias . '.' . $relationField;
                $queryBuilder->innerJoin($joinString, $relationField);
            }
            /* It's allowed to link maximum 3 levels of the relation separated by dots.
             Defined in the formatter like in the following example: 'fieldName' => 'measure.referential.uuid'. */
            $nextFieldNamePart = next($fieldParts);
            if ($nextFieldNamePart !== false && $fieldNamePart !== $nextFieldNamePart) {
                $this->linkRelationAndGetFiledNameWithAlias(
                    $queryBuilder,
                    $relationField,
                    $fieldNamePart . '.' . $nextFieldNamePart
                );
                $relationField = $fieldNamePart;
                $fieldNamePart = $nextFieldNamePart;
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
