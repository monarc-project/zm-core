<?php declare(strict_types=1);

namespace Unit\Service\Traits;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Interfaces\PositionedEntityInterface;
use Monarc\Core\Model\Entity\Traits\PropertyStateEntityTrait;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Table\AbstractTable;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use PHPUnit\Framework\TestCase;

class PositionUpdateTraitTest extends TestCase
{
    /* START Precondition test cases. */
    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testUpdatePositionCallWhenTableIsNotSubclassOfAbstractTable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Table should be subclass of "' . AbstractTable::class . '"');

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $entity = $this->getMockBuilder(PositionedEntityInterface::class)->getMock();
        $table = $this->getMockBuilder(PositionUpdatableTableInterface::class)->getMock();

        $positionUpdateTrait->updatePositions($entity, $table, []);
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testUpdatePositionWhenEntityClassesAreNotEqual(): void
    {
        $entity = $this->getMockBuilder(PositionedEntityInterface::class)->getMock();
        $positionedEntity = $this->getTestClassOfPositionedEntity();
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(1, $positionedEntity);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Table\'s entity class name "%s" and entity class name "%s" should be equal.',
                $table->getEntityName(),
                \get_class($entity)
            )
        );

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, []);
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testUpdatePositionIfImplicitPositionAfterThenPreviousMandatory(): void
    {
        $entity = $this->getMockBuilder(PositionedEntityInterface::class)->getMock();
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(1, $entity);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'To set the implicit position after another element, the "previous" param is mandatory.'
        );

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER
        ]);
    }
    /* END Precondition test cases. */

    /* START Logical test cases. */
    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionEndByDefault(): void
    {
        $entity = $this->getTestClassOfPositionedEntity();
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(10, $entity);

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, []);

        static::assertEquals(11, $entity->getPosition());
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionEndUpdateForExistedEntityWithForceUpdateParam(): void
    {
        $entity = $this->getTestClassOfPositionedEntity(100);
        /** @var AbstractTable|PositionUpdatableTableInterface $table */
        $table = $this->getTestClassOfPositionUpdatableTable(7, $entity, false);

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_END,
            'forcePositionUpdate' => true,
        ]);

        $incrementPositionParams = $table->getIncrementPositionParams();

        static::assertCount(1, $incrementPositionParams);
        static::assertSame([
            [
                'positionFrom' => 101,
                'positionTo' => -1,
                'increment' => -1,
                'params' => ['field1' => 'value1'],
                'updater' => 'updater',
            ]
        ], $incrementPositionParams);
        static::assertEquals(7, $entity->getPosition());
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionEndUpdateForExistedEntityWithoutForceUpdateParam(): void
    {
        $entity = $this->getTestClassOfPositionedEntity(100);
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(7, $entity, false);

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_END,
        ]);

        $incrementPositionParams = $table->getIncrementPositionParams();

        static::assertCount(0, $incrementPositionParams);
        static::assertEquals(100, $entity->getPosition());
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionStartForNewEntity(): void
    {
        $entity = $this->getTestClassOfPositionedEntity(777);
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(10, $entity);

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_START
        ]);

        $incrementPositionParams = $table->getIncrementPositionParams();

        static::assertCount(1, $incrementPositionParams);
        static::assertSame([
            [
                'positionFrom' => 1,
                'positionTo' => -1,
                'increment' => 1,
                'params' => ['field1' => 'value1'],
                'updater' => 'creator',
            ]
        ], $incrementPositionParams);
        static::assertEquals(1, $entity->getPosition());
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionStartForExistingEntityWithForceUpdateParam(): void
    {
        $entity = $this->getTestClassOfPositionedEntity(777);
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(10, $entity, false);

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_START,
            'forcePositionUpdate' => true,
        ]);

        $incrementPositionParams = $table->getIncrementPositionParams();

        static::assertCount(1, $incrementPositionParams);
        static::assertSame([
            [
                'positionFrom' => 1,
                'positionTo' => 777,
                'increment' => 1,
                'params' => ['field1' => 'value1'],
                'updater' => 'updater',
            ]
        ], $incrementPositionParams);
        static::assertEquals(1, $entity->getPosition());
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionAfterForNewEntityWithIntegerId(): void
    {
        $entity = $this->getTestClassOfPositionedEntity(555);
        $previousEntity = $this->getTestClassOfPositionedEntity(15);
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(15, $entity, true, $previousEntity);

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER,
            'previous' => 1,
        ]);

        $incrementPositionParams = $table->getIncrementPositionParams();

        static::assertCount(1, $incrementPositionParams);
        static::assertSame([
            [
                'positionFrom' => 16,
                'positionTo' => -1,
                'increment' => 1,
                'params' => ['field1' => 'value1'],
                'updater' => 'creator',
            ]
        ], $incrementPositionParams);
        static::assertEquals(16, $entity->getPosition());
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionAfterForNewEntityWithStringId(): void
    {
        $entity = $this->getTestClassOfPositionedEntity(123);
        $previousEntity = $this->getTestClassOfPositionedEntity(25);
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(15, $entity, true, $previousEntity);

        /** @var PositionUpdateTrait|object $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        static::assertEquals(0, $entity->getAnrTimesCalled());

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER,
            'previous' => 'abc-123-dfg',
        ]);

        $incrementPositionParams = $table->getIncrementPositionParams();

        static::assertEquals(2, $entity->getAnrTimesCalled());
        static::assertCount(1, $incrementPositionParams);
        static::assertSame([
            [
                'positionFrom' => 26,
                'positionTo' => -1,
                'increment' => 1,
                'params' => ['field1' => 'value1'],
                'updater' => 'creator',
            ]
        ], $incrementPositionParams);
        static::assertEquals(26, $entity->getPosition());
    }

    /**
     * @covers PositionUpdateTrait::updatePositions
     */
    public function testImplicitPositionAfterForExisted(): void
    {
        $entity = $this->getTestClassOfPositionedEntity(14);
        $previousEntity = $this->getTestClassOfPositionedEntity(27);
        /** @var AbstractTable $table */
        $table = $this->getTestClassOfPositionUpdatableTable(11, $entity, false, $previousEntity);

        /** @var PositionUpdateTrait $positionUpdateTrait */
        $positionUpdateTrait = $this->getObjectForTrait(PositionUpdateTrait::class);

        $positionUpdateTrait->updatePositions($entity, $table, [
            'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER,
            'previous' => 1,
        ]);

        $incrementPositionParams = $table->getIncrementPositionParams();

        static::assertCount(2, $incrementPositionParams);
        static::assertSame([
            [
                'positionFrom' => 15,
                'positionTo' => -1,
                'increment' => -1,
                'params' => ['field1' => 'value1'],
                'updater' => 'updater',
            ],
            [
                'positionFrom' => 28,
                'positionTo' => -1,
                'increment' => 1,
                'params' => ['field1' => 'value1'],
                'updater' => 'updater',
            ],
        ], $incrementPositionParams);
        static::assertEquals(28, $entity->getPosition());
    }
    /* END Logical test cases. */

    /* Test classes builders. */
    private function getTestClassOfPositionUpdatableTable(
        int $expectedMaxPos,
        PositionedEntityInterface $entity,
        bool $isNewEntity = true,
        PositionedEntityInterface $previousEntity = null
    ): PositionUpdatableTableInterface {
        return new class(
            $expectedMaxPos,
            $this->createMock(EntityManager::class),
            $entity,
            $isNewEntity,
            $previousEntity
        ) extends AbstractTable implements PositionUpdatableTableInterface
        {
            private int $expectedMaxPos;
            private bool $isNewEntity;
            private array $incrementPositionParams = [];
            private ?PositionedEntityInterface $previousEntity;

            public function __construct(
                $expectedMaxPos,
                EntityManager $entityManager,
                PositionedEntityInterface $entity,
                bool $isNewEntity,
                ?PositionedEntityInterface $previousEntity
            ) {
                $this->expectedMaxPos = $expectedMaxPos;
                $this->isNewEntity = $isNewEntity;
                $this->previousEntity = $previousEntity;

                parent::__construct($entityManager, \get_class($entity));
            }

            public function incrementPositions(
                int $positionFrom,
                int $positionTo,
                int $increment,
                array $params,
                string $updater
            ): void {
                $this->incrementPositionParams[] = compact(
                    'positionFrom',
                    'positionTo',
                    'increment',
                    'params',
                    'updater'
                );
            }

            public function findMaxPosition(array $params): int
            {
                return $this->expectedMaxPos;
            }

            public function isEntityPersisted(object $entity): bool
            {
                return !$this->isNewEntity;
            }

            public function findById($id, bool $throwErrorIfNotFound = true): ?object
            {
                return $this->previousEntity;
            }

            public function getIncrementPositionParams(): array
            {
                return $this->incrementPositionParams;
            }
        };
    }

    private function getTestClassOfPositionedEntity(int $currentPosition = 1): PositionedEntityInterface
    {
        return new class($currentPosition) implements PositionedEntityInterface {
            private int $position;
            private int $getAnrTimesCalled = 0;

            // TODO: perform the used PropertyStateEntityTrait methods testing.
            use PropertyStateEntityTrait;

            public function __construct(int $currentPosition)
            {
                $this->position = $currentPosition;
            }

            public function getPosition(): int
            {
                return $this->position;
            }

            public function setPosition(int $position): PositionedEntityInterface
            {
                $this->position = $position;

                return $this;
            }

            public function getImplicitPositionRelationsValues(): array
            {
                return ['field1' => 'value1'];
            }

            public function getAnr(): ?AnrSuperClass
            {
                $this->getAnrTimesCalled++;

                return new AnrSuperClass();
            }

            public function getAnrTimesCalled(): int
            {
                return $this->getAnrTimesCalled;
            }

            public function getCreator(): string
            {
                return 'creator';
            }

            public function getUpdater(): string
            {
                return 'updater';
            }
        };
    }
}
