<?php declare(strict_types=1);

namespace Unit\Table;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Monarc\Core\Entity\MonarcObject;
use Monarc\Core\Entity\ObjectSuperClass;
use Monarc\Core\Table\MonarcObjectTable;
use PHPUnit\Framework\TestCase;

final class MonarcObjectTableTest extends TestCase
{
    /** @covers \Monarc\Core\Table\MonarcObjectTable::findByMode */
    public function testFindByModeReadsOnlyTheRequestedCommonObjectScope(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())->method('getResult')->willReturn([]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())->method('where')->with('object.mode = :mode')->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('mode', ObjectSuperClass::MODE_GENERIC)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('createQueryBuilder')->with('object')->willReturn($queryBuilder);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(MonarcObject::class)
            ->willReturn($repository);

        $table = new MonarcObjectTable($entityManager);

        self::assertSame([], $table->findByMode(ObjectSuperClass::MODE_GENERIC));
    }
}
