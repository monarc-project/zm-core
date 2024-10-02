<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table\Traits;

use Doctrine\ORM\QueryBuilder;
use Monarc\Core\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Table\AbstractTable;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;

trait PositionIncrementTableTrait
{
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
        if (!$this instanceof PositionUpdatableTableInterface || !is_subclass_of($this, AbstractTable::class)) {
            throw new \LogicException(
                'The trait "PositionIncrementTableTrait" is used in the wrong table class "' . \get_class($this) . '".'
            );
        }

        if ($increment === 0) {
            return;
        }

        $queryBuilder = $this->getRepository()->createQueryBuilder('t');
        $this->prepareQueryBuilderParams($queryBuilder, $params);

        $queryBuilder->andWhere('t.position >= :positionFrom')->setParameter('positionFrom', $positionFrom);
        if ($positionTo > $positionFrom) {
            $queryBuilder->andWhere('t.position <= :positionTo')->setParameter('positionTo', $positionTo);
        }

        /** @var PositionedEntityInterface $entity */
        foreach ($queryBuilder->getQuery()->getResult() as $entity) {
            $newPosition = $increment > 0
                ? $entity->getPosition() + $increment
                : $entity->getPosition() - abs($increment);

            $entity->setPosition($newPosition)->setUpdater($updater);
            $this->save($entity, false);
        }
    }

    public function findMaxPosition(array $params): int
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t')->select('MAX(t.position)');

        $this->prepareQueryBuilderParams($queryBuilder, $params);

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function prepareQueryBuilderParams(QueryBuilder $queryBuilder, array $params): void
    {
        foreach ($params as $fieldName => $fieldValue) {
            if (\is_array($fieldValue)) {
                $queryBuilder->innerJoin('t.' . $fieldName, $fieldName);
                foreach ($fieldValue as $relationFieldName => $relationFieldValue) {
                    $relationFieldParam = $fieldName . '_' . $relationFieldName;
                    $queryBuilder->andWhere($fieldName . '.' . $relationFieldName . ' = :' . $relationFieldParam)
                        ->setParameter($relationFieldParam, $relationFieldValue);
                }
            } elseif ($fieldValue === null) {
                $queryBuilder->andWhere('t.' . $fieldName . ' IS NULL');
            } else {
                $queryBuilder->andWhere('t.' . $fieldName . ' = :' . $fieldName)->setParameter($fieldName, $fieldValue);
            }
        }
    }
}
