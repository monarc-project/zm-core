<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table\Traits;

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
        if (!$this instanceof PositionUpdatableTableInterface
            || !is_subclass_of($this, AbstractTable::class)
        ) {
            return;
        }

        $positionShift = $increment > 0 ? '+ ' . $increment : '- ' . abs($increment);
        $queryBuilder = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position ' . $positionShift)
            ->set('t.updater', ':updater')
            ->setParameter('updater', $updater);

        foreach ($params as $fieldName => $fieldValue) {
            if ($fieldValue === null) {
                $queryBuilder->andWhere('t.' . $fieldName . ' IS NULL');
            } else {
                $queryBuilder->andWhere('t.' . $fieldName . ' = :' . $fieldName)
                    ->setParameter($fieldName, $fieldValue);
            }
        }

        $queryBuilder->andWhere('t.position >= :positionFrom')
            ->setParameter('positionFrom', $positionFrom);
        if ($positionTo > $positionFrom) {
            $queryBuilder->andWhere('t.position <= :positionTo')
                ->setParameter('positionTo', $positionTo);
        }

        $queryBuilder->getQuery()->getResult();
    }
}
