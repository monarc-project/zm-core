<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table\Traits;

use Doctrine\ORM\QueryBuilder;
use Monarc\Core\Table\AbstractTable;

trait CodeExistenceValidationTableTrait
{
    /**
     * @param array $includeFilter List of fields to add to "where =" clause, e.g.
     *                              ['anr' => $anr, 'referential' => ['uuid' => 'uuid_val', 'anr' => $anr]]
     * @param array $excludeFilter List of fields to add to "where <>" clause, e.g.
     *                              ['anr' => $anr, 'recommendationSet => ['uuid' => 'uuid_val', 'anr' => $anr]]
     */
    public function doesCodeAlreadyExist(string $code, array $includeFilter = [], array $excludeFilter = []): bool
    {
        if (!is_subclass_of($this, AbstractTable::class)) {
            throw new \LogicException(
                'The trait "CodeExistenceValidationTableTrait" is used in the wrong table class "'
                . \get_class($this) . '".'
            );
        }

        $queryBuilder = $this->getRepository()->createQueryBuilder('t')
            ->select('t.code')
            ->where('t.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1);
        $this->addFilterParameters($queryBuilder, $includeFilter);
        $this->addFilterParameters($queryBuilder, $excludeFilter, false);

        return (bool)$queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function addFilterParameters(
        QueryBuilder $queryBuilder,
        array $filterParams,
        bool $includeClause = true
    ): void {
        foreach ($filterParams as $fieldName => $fieldValue) {
            if (\is_array($fieldValue)) {
                $queryBuilder->innerJoin('t.' . $fieldName, $fieldName);
                foreach ($fieldValue as $relationFieldName => $relationFieldValue) {
                    $relationFieldParam = $fieldName . '_' . $relationFieldName;
                    $queryBuilder->andWhere(
                        $fieldName . '.' . $relationFieldName . ($includeClause ? ' = ' : ' <> ')
                        . ' :' . $relationFieldParam
                    )->setParameter($relationFieldParam, $relationFieldValue);
                }
            } elseif ($fieldValue === null) {
                $queryBuilder->andWhere('t.' . $fieldName . ($includeClause ? ' IS NULL' : ' IS NOT NULL'));
            } else {
                $queryBuilder->andWhere('t.' . $fieldName . ($includeClause ? ' = ' : ' <> ') . ':' . $fieldName)
                    ->setParameter($fieldName, $fieldValue);
            }
        }
    }
}
