<?php declare(strict_types=1);

namespace Unit\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Monarc\Core\Table\AbstractTable;
use PHPUnit\Framework\TestCase;

class AbstractTableTest extends TestCase
{
    /**
     * @covers AbstractTable::linkRelationAndGetFiledNameWithAlias
     */
    public function testLinkRelationAndGetFiledNameWithAliasForDotSeparatedFields(): void
    {
//        $params = [
//            'search' => [
//                'fields' => ['relation1.field5', 'relation2.field7'],
//                'string' => 'test',
//                'operand' => 'OR',
//            ],
//            'filter' => [
//                'status' => 1,
//            ],
//        ];
//        $order = [
//            'name' => 'DESC'
//        ];

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $abstractTableStub = $this->getMockForAbstractClass(
            AbstractTable::class,
            [
                $entityManager,
                'MyEntity',
            ]
        );

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('getAllAliases')->willReturn([]);

        $result = static::invokeMethod($abstractTableStub, 'linkRelationAndGetFiledNameWithAlias', [
            $queryBuilderMock,
            'table',
            'relation1.field5',
        ]);
        static::assertEquals('relation1.field5', $result);

        // $result = $abstractTableStub->findByParams($params, $order);
    }

    protected static function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
