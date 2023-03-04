<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Table\AbstractTable;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;

trait PositionUpdateTrait
{
    /**
     * @param PositionedEntityInterface $entity
     * @param PositionUpdatableTableInterface|AbstractTable $table
     * @param array $data Note: ['forcePositionUpdate' => true] is used to update the position for already persisted.
     *
     * @throws Exception|LogicException|EntityNotFoundException
     */
    public function updatePositions(
        PositionedEntityInterface $entity,
        PositionUpdatableTableInterface $table,
        array $data = []
    ): void {
        $this->validateParams($entity, $table, $data);

        $implicitPosition = $data['implicitPosition'] ?? PositionUpdatableServiceInterface::IMPLICIT_POSITION_END;

        /* Update the position of already persisted entities is performed only when it's after another element,
         * or if forcePositionUpdate is passed. */
        $isEntityPersisted = $table->isEntityPersisted($entity);
        if ($isEntityPersisted
            && $implicitPosition !== PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER
            && empty($data['forcePositionUpdate'])
        ) {
            return;
        }

        $implicitPositionsValues = $entity->getImplicitPositionRelationsValues();
        $areImplicitPositionsValuesChanged = $entity->arePropertiesStatesChanged($implicitPositionsValues);
        $updater = $isEntityPersisted ? $entity->getUpdater() : $entity->getCreator();
        switch ($implicitPosition) {
            case PositionUpdatableServiceInterface::IMPLICIT_POSITION_START:
                /* If the entity is new or the implicit positions values are changed (root, parent, etc),
                 * it's necessary to shift all the positions within the new place to allocate the position 1. */
                if (!$isEntityPersisted || $areImplicitPositionsValuesChanged) {
                    $table->incrementPositions(1, -1, 1, $implicitPositionsValues, $entity->getCreator());
                    /* Populate the entity position within the previous implicit position set (not occupied for now). */
                    if ($areImplicitPositionsValuesChanged) {
                        $table->incrementPositions(
                            $entity->getPosition() + 1,
                            -1,
                            -1,
                            $entity->getPropertiesStates($implicitPositionsValues),
                            $updater
                        );
                    }
                } elseif ($entity->getPosition() > 1) {
                    $currentPosition = $entity->getPosition();
                    $table->incrementPositions(1, $currentPosition, 1, $implicitPositionsValues, $updater);
                }

                $entity->setPosition(1);

                break;
            case PositionUpdatableServiceInterface::IMPLICIT_POSITION_END:
                $maxPosition = $table->findMaxPosition($implicitPositionsValues);
                if (!$isEntityPersisted || $areImplicitPositionsValuesChanged) {
                    /* Populate the entity position within the previous implicit position set (not occupied for now). */
                    if ($areImplicitPositionsValuesChanged) {
                        $table->incrementPositions(
                            $entity->getPosition() + 1,
                            -1,
                            -1,
                            $entity->getPropertiesStates($implicitPositionsValues),
                            $updater
                        );
                    }
                    $entity->setPosition($maxPosition + 1);
                } elseif ($entity->getPosition() !== $maxPosition) {
                    $table->incrementPositions($entity->getPosition() + 1, -1, -1, $implicitPositionsValues, $updater);
                    $entity->setPosition($maxPosition);
                }

                break;
            case PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER:
                if ($data['previous'] instanceof PositionedEntityInterface) {
                    $previousEntity = $data['previous'];
                } else {
                    $entityKey = $data['previous'];
                    /* Some entities have 2 fields (uuid, anr) relation, sp anr is added to the search in this case. */
                    if (\is_string($entityKey) && $entity->getAnr() !== null) {
                        $entityKey = [
                            'uuid' => $entityKey,
                            'anr' => $entity->getAnr(),
                        ];
                    }
                    /** @var PositionedEntityInterface $previousEntity */
                    $previousEntity = $table->findById($entityKey);
                }

                $expectedPosition = $previousEntity->getPosition() + 1;
                if ($isEntityPersisted
                    && !$areImplicitPositionsValuesChanged
                    && $entity->getPosition() !== $expectedPosition
                ) {
                    /* Shift the elements to fill the previous position. The element will have a new one. */
                    $table->incrementPositions($entity->getPosition() + 1, -1, -1, $implicitPositionsValues, $updater);
                    $table->refresh($previousEntity);
                    $expectedPosition = $previousEntity->getPosition() + 1;
                } elseif ($areImplicitPositionsValuesChanged) {
                    /* Populate the entity position within the previous implicit position set (not occupied for now). */
                    $table->incrementPositions(
                        $entity->getPosition() + 1,
                        -1,
                        -1,
                        $entity->getPropertiesStates($implicitPositionsValues),
                        $updater
                    );
                }

                /* Shift the elements to allocate a place for the new element's position. */
                $table->incrementPositions($expectedPosition, -1, 1, $implicitPositionsValues, $updater);
                $entity->setPosition($expectedPosition);

                break;
        }
    }

    public function shiftPositionsForRemovingEntity(
        PositionedEntityInterface $entity,
        PositionUpdatableTableInterface $table
    ): void {
        /* Shift the elements to the position of the removing entity. */
        $table->incrementPositions(
            $entity->getPosition() + 1,
            -1,
            -1,
            $entity->getImplicitPositionRelationsValues(),
            $entity->getUpdater()
        );
        /* Set the position of the removing to 0 that it is not counted anymore. */
        $entity->setPosition(0);
    }

    /**
     * @throws Exception|LogicException
     */
    private function validateParams(object $entity, PositionUpdatableTableInterface $table, array $data): void
    {
        if (!is_subclass_of($table, AbstractTable::class)) {
            throw new LogicException('Table should be subclass of "' . AbstractTable::class . '"');
        }
        if ($table->getEntityName() !== \get_class($entity)) {
            throw new LogicException(
                sprintf(
                    'Table\'s entity class name "%s" and entity class name "%s" should be equal.',
                    $table->getEntityName(),
                    \get_class($entity)
                )
            );
        }
        if (isset($data['implicitPosition'])
            && $data['implicitPosition'] === PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER
            && !isset($data['previous'])
        ) {
            throw new Exception(
                'To set the implicit position after another element, the "previous" param is mandatory.',
                412
            );
        }
    }
}
