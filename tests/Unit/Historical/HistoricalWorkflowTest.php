<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Historical;

use Monarc\Core\Entity;
use Monarc\Core\InputFormatter\Historical\GetHistoricalsInputFormatter;
use Monarc\Core\Service;
use Monarc\Core\Table;
use Monarc\Core\Tests\Fixture\HistoricalTestEnvironment;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
class HistoricalWorkflowTest extends TestCase
{
    private HistoricalTestEnvironment $env;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fixture/HistoricalTestEnvironment.php';
        $identity = $this->createMock(Service\ConnectedUserService::class);
        $user = $this->createMock(Entity\UserSuperClass::class);
        $user->method('getEmail')->willReturn('history@example.invalid');
        $identity->method('getConnectedUser')->willReturn($user);
        $this->env = new HistoricalTestEnvironment($identity);
    }

    protected function tearDown(): void
    {
        $this->env->close();
    }

    public function testMappingAndTableFactoryUseTheCommonManager(): void
    {
        $metadata = $this->env->manager->getClassMetadata(Entity\Historical::class);
        self::assertSame('string', $metadata->fieldMappings['sourceId']['type']);
        self::assertSame(64, $metadata->fieldMappings['sourceId']['length']);
        self::assertTrue($metadata->fieldMappings['sourceId']['nullable']);
        self::assertFalse(method_exists(Entity\Historical::class, 'setId'));
        $config = require __DIR__ . '/../../../config/module.config.php';
        $factoryClass = $config['service_manager']['factories'][Table\HistoricalTable::class];
        self::assertSame(Table\Factory\CoreEntityManagerFactory::class, $factoryClass);
        $container = new \Laminas\ServiceManager\ServiceManager(['services' => [
            'doctrine.entitymanager.orm_default' => $this->env->manager,
        ]]);
        $table = (new $factoryClass())($container, Table\HistoricalTable::class);
        $this->env->service->create(['label1' => 'Common data']);
        self::assertSame(1, $table->countAll());
        self::assertSame(
            Table\Factory\CoreEntityManagerFactory::class,
            $config['service_manager']['factories'][Table\AmvTable::class]
        );
    }

    public function testDeferredAndStandaloneWritesUseFreshGeneratedIds(): void
    {
        $first = $this->env->service->create(['sourceId' => 123, 'label1' => 'First'], false);
        self::assertSame(0, $this->env->table->countAll());
        self::assertFalse((new \ReflectionProperty(Entity\Historical::class, 'id'))->isInitialized($first));
        $second = $this->env->service->create(['sourceId' => '5c732341-2aa6-4d28-b1f3-bff6ecbc1339']);
        self::assertSame(2, $this->env->table->countAll());
        self::assertNotSame($first->getId(), $second->getId());
        self::assertSame('123', $first->getSourceId());
        self::assertSame('history@example.invalid', $second->getCreator());
        self::assertNotNull($second->getCreatedAt());
        self::assertSame('', $first->getLabel(4));
        $this->env->manager->clear();
        self::assertSame($second->getSourceId(), $this->env->table->findById($second->getId())->getSourceId());
    }

    public function testLegacyNullsAuditDatesAndAllUiQueriesRemainReadable(): void
    {
        $db = $this->env->manager->getConnection();
        $db->insert('historicals', ['type' => null, 'details' => null, 'source_id' => '00042']);
        $db->insert('historicals', [
            'type' => 'amv',
            'action' => 'create',
            'label1' => 'Unchanged',
            'label2' => 'English needle',
            'label3' => 'Deutsch',
            'label4' => 'Nederlands',
            'source_id' => '5c732341-2aa6-4d28-b1f3-bff6ecbc1339',
            'details' => 'asset => A /  threat => T / vulnerability => V',
            'creator' => 'Legacy author',
            'created_at' => '2001-02-03 04:05:06',
        ]);
        $db->insert('historicals', ['details' => '']);
        $before = $db->fetchAllAssociative('SELECT * FROM historicals ORDER BY id');
        $formatter = new GetHistoricalsInputFormatter();
        $rows = $this->env->service->getList($formatter->format(['order' => 'id']));
        self::assertSame('00042', $rows[0]['sourceId']);
        self::assertNull($rows[0]['label1']);
        self::assertNull($rows[0]['action']);
        self::assertSame('', $rows[0]['createdAt']);
        self::assertSame([''], $rows[0]['details']);
        self::assertSame([''], $rows[2]['details']);
        self::assertSame('03/02/2001 04:05:06', $rows[1]['createdAt']);
        self::assertSame('Legacy author', $rows[1]['creator']);
        self::assertSame(['asset => A', ' threat => T', 'vulnerability => V'], $rows[1]['details']);
        foreach (['amv', 'create', 'Unchanged', 'needle', 'Deutsch', 'Nederlands', 'Legacy author'] as $term) {
            $params = $formatter->format(['filter' => $term, 'page' => 1, 'limit' => 1, 'order' => '-createdAt']);
            self::assertSame(1, $this->env->service->getCount($params));
            self::assertSame($rows[1], $this->env->service->getList($params)[0]);
        }
        foreach (['type', 'action', 'label1', 'creator', 'createdAt'] as $field) {
            self::assertCount(3, $this->env->service->getList($formatter->format(['order' => '-' . $field])));
        }
        $params = $formatter->format(['page' => 2, 'limit' => 2, 'order' => 'id']);
        self::assertCount(1, $this->env->service->getList($params));
        self::assertSame(3, $this->env->service->getCount($params));
        self::assertSame([], $this->env->service->getList($formatter->format(['filter' => "' OR 1=1 --"])));
        self::assertSame($before, $db->fetchAllAssociative('SELECT * FROM historicals ORDER BY id'));
    }

    public function testActualAmvCreateUpdateDeleteQueuesHistoryUntilFinalWrite(): void
    {
        $manager = $this->env->manager;
        $asset = (new Entity\Asset())->setType(Entity\Asset::TYPE_SECONDARY)->setCode('A');
        $threat = (new Entity\Threat())->setCode('T');
        $otherThreat = (new Entity\Threat())->setCode('T2');
        $vulnerability = (new Entity\Vulnerability())->setCode('V');
        foreach ([$asset, $threat, $otherThreat, $vulnerability] as $reference) {
            $manager->persist($reference);
        }
        $manager->flush();
        $db = $manager->getConnection();
        $amvTable = $this->getMockBuilder(Table\AmvTable::class)->setConstructorArgs([$manager])
            ->onlyMethods(['save', 'remove'])->getMock();
        $expectedHistoryCount = 0;
        $expectedAmvCount = 0;
        $amvTable->method('save')->willReturnCallback(
            static function (
                object $amv,
                bool $flush = true
            ) use (
                $manager,
                $db,
                &$expectedHistoryCount,
                &$expectedAmvCount
            ): void {
                self::assertSame($expectedHistoryCount, (int)$db->fetchOne('SELECT COUNT(*) FROM historicals'));
                self::assertSame($expectedAmvCount, (int)$db->fetchOne('SELECT COUNT(*) FROM amvs'));
                if ($flush) {
                    self::assertCount(1, array_filter(
                        $manager->getUnitOfWork()->getScheduledEntityInsertions(),
                        static fn (object $record): bool => $record instanceof Entity\Historical
                    ));
                }
                $manager->persist($amv);
                if ($flush) {
                    $manager->flush();
                }
            }
        );
        $amvTable->expects(self::once())->method('remove')->willReturnCallback(
            static function (object $amv) use ($manager, $db): void {
                self::assertSame(2, (int)$db->fetchOne('SELECT COUNT(*) FROM historicals'));
                self::assertSame(1, (int)$db->fetchOne('SELECT COUNT(*) FROM amvs'));
                $manager->remove($amv);
                $manager->flush();
            }
        );
        $service = new Service\AmvService(
            $amvTable,
            new Table\AssetTable($manager),
            new Table\ThreatTable($manager),
            new Table\VulnerabilityTable($manager),
            new Table\MeasureTable($manager),
            new Table\ReferentialTable($manager),
            new Table\ModelTable($manager),
            $this->env->service,
            $this->createMock(Service\AssetService::class),
            $this->createMock(Service\ThreatService::class),
            $this->createMock(Service\VulnerabilityService::class),
            $this->createMock(Service\InstanceRiskService::class),
            $this->env->connectedUserService
        );
        $data = [
            'asset' => $asset->getUuid(),
            'threat' => $threat->getUuid(),
            'vulnerability' => $vulnerability->getUuid(),
            'measures' => [],
        ];
        $amv = $service->create($data);
        self::assertNotSame('', $amv->getUuid());
        $expectedHistoryCount = 1;
        $expectedAmvCount = 1;
        $service->update($amv->getUuid(), array_replace($data, ['threat' => $otherThreat->getUuid()]));
        $service->delete($amv->getUuid());
        $rows = $this->env->service->getList((new GetHistoricalsInputFormatter())->format(['order' => 'id']));
        self::assertCount(3, $rows);
        self::assertSame(['create', 'create', 'delete'], array_column($rows, 'action'));
        self::assertSame(['A - T - V', 'A - T - V', 'A - T2 - V'], array_column($rows, 'label1'));
        self::assertSame(['T => T2'], $rows[1]['details']);
        foreach ($rows as $row) {
            self::assertSame($amv->getUuid(), $row['sourceId']);
            self::assertSame('amv', $row['type']);
            self::assertSame('history@example.invalid', $row['creator']);
            self::assertNotSame('', $row['createdAt']);
            self::assertSame($row['label1'], $row['label2']);
            self::assertSame($row['label1'], $row['label3']);
            self::assertSame($row['label1'], $row['label4']);
        }
        self::assertCount(3, array_unique(array_column($rows, 'id')));
        self::assertSame(0, (int)$db->fetchOne('SELECT COUNT(*) FROM amvs'));
    }
}
