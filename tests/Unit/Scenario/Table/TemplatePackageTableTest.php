<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Scenario\Table;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Scenario\Table\CatalogTable;
use Monarc\Core\Scenario\Table\PackageSourceTable;
use PHPUnit\Framework\TestCase;

/** @covers \Monarc\Core\Scenario\Table\PackageSourceTable
 * @covers \Monarc\Core\Scenario\Table\CatalogTable
 */
final class TemplatePackageTableTest extends TestCase
{
    private const UUID = '00000000-0000-4000-8000-000000000001';

    public function testEverySupportedSourceHasReadableBoundedSnapshotAndHierarchy(): void
    {
        [$db, $catalog, $sources] = $this->tables();
        foreach (PackageSourceTable::TYPES as $type) {
            $source = $sources->snapshot($type, $type === 'risk-source' ? '1' : self::UUID);
            self::assertNotNull($source, $type);
            self::assertSame('common-knowledge-base', $source['sourceScope']);
            self::assertSame($type, $source['type']);
            self::assertNotEmpty($source['label']);
            self::assertSame(64, strlen($source['checksum']));
            self::assertArrayNotHasKey('creator', $source);
            self::assertCount(1, $sources->list($type, '', 1, 25)['items']);
        }
        self::assertSame('Library asset description', $sources->snapshot('asset', self::UUID)['description']);
        self::assertSame('referential', $sources->snapshot('control', self::UUID)['parent']['type']);
        $db->update('assets', ['status' => 0], ['uuid' => self::UUID]);
        self::assertNull($sources->snapshot('asset', self::UUID));
        self::assertNull($sources->snapshot('asset-type', self::UUID));
        $db->update('measures', ['status' => 0], ['uuid' => self::UUID]);
        self::assertNull($sources->snapshot('control', self::UUID));
        self::assertNull($sources->snapshot('risk-source', '99'));
    }

    public function testPublishedPackageSurvivesSourceEditsRemovalAndIndependentSuccessor(): void
    {
        [$db, $catalog, $sources] = $this->tables();
        $db->insert('scenario_catalog_templates', ['id' => 1, 'stable_uuid' => self::UUID, 'version' => 1]);
        self::assertTrue($catalog->claimTemplatePackageRevision(1, 1));
        self::assertFalse($catalog->claimTemplatePackageRevision(1, 1));
        $links = array_map(static fn (string $type): array => [
            'type' => $type, 'sourceIdentity' => $type === 'risk-source' ? '1' : self::UUID,
            'guidance' => 'Review locally',
        ], array_reverse(PackageSourceTable::TYPES));
        $catalog->replaceTemplatePackage(1, $links);
        $catalog->captureTemplatePackage(1, $sources, true);
        $snapshot = $catalog->findTemplatePackage(1);
        self::assertSame(array_column($links, 'type'), array_column($snapshot, 'type'));
        self::assertSame(range(1, 7), array_column($snapshot, 'position'));
        $db->update('scenario_catalog_templates', ['lifecycle' => 'published'], ['id' => 1]);
        self::assertFalse($catalog->claimTemplatePackageRevision(1, 2));
        $db->insert('scenario_catalog_templates', ['id' => 2, 'stable_uuid' => self::UUID, 'version' => 2]);
        $catalog->replaceTemplatePackage(2, $snapshot);
        self::assertNull($catalog->findTemplatePackage(2)[0]['source']);
        $db->update('measures', ['label1' => 'Changed control'], ['uuid' => self::UUID]);
        $catalog->captureTemplatePackage(2, $sources, true);
        self::assertNotSame($snapshot, $catalog->findTemplatePackage(2));
        $db->delete('measures', ['uuid' => self::UUID]);
        self::assertSame($snapshot, $catalog->findTemplatePackage(1));
        self::assertSame($snapshot, $catalog->findPublishedTemplateSnapshot(self::UUID, 1)['knowledgeBasePackage']);
    }

    public function testRiskSourceJsonLabelsAreReadableAuthorContent(): void
    {
        [$db, $catalog, $sources] = $this->tables();
        $db->update('risk_sources', ['label' => '{"fr":"Attaquant externe","en":"External attacker"}'], ['id' => 1]);
        self::assertSame('Attaquant externe', $sources->snapshot('risk-source', '1')['label']);
        $db->update('risk_sources', ['label' => '{}'], ['id' => 1]);
        self::assertNull($sources->snapshot('risk-source', '1'));
    }

    public function testNumericSourceIdentityMustMatchTheLocatedRecordWithoutDatabaseCoercion(): void
    {
        $db = $this->createMock(\Doctrine\DBAL\Connection::class);
        // MariaDB can match an integer key against a string beginning with that integer.
        $db->method('fetchAssociative')->willReturn(['id' => 1, 'source_label' => 'Risk source']);
        $em = $this->createMock(EntityManager::class);
        $em->method('getConnection')->willReturn($db);
        $sources = new PackageSourceTable($em);
        self::assertNull($sources->snapshot('risk-source', '1 OR 1=1'));
        self::assertNull($sources->snapshot('risk-source', '01'));
        self::assertNotNull($sources->snapshot('risk-source', '1'));
    }

    public function testReferentialControlsAreResolvedAsActiveSourceSnapshots(): void
    {
        [$db, , $sources] = $this->tables();
        $secondControl = '00000000-0000-4000-8000-000000000002';
        $db->insert('measures', [
            'uuid' => $secondControl,
            'referential_uuid' => self::UUID,
            'label1' => 'Second control',
            'status' => 1,
        ]);

        self::assertSame(
            [self::UUID, $secondControl],
            array_column($sources->controlsForReferential(self::UUID), 'identity')
        );
    }

    public function testUnsupportedTablesAndUnavailableSourcesAreRejected(): void
    {
        [$db, $catalog, $sources] = $this->tables();
        $this->expectException(\InvalidArgumentException::class);
        $sources->snapshot('recommandations', self::UUID);
    }

    /** @dataProvider unsupportedPackageValues */
    public function testUnsupportedTypesCannotReplaceExistingPackageLinks(string $type): void
    {
        [$db, $catalog] = $this->tables();
        $db->insert('scenario_catalog_templates', ['id' => 1, 'stable_uuid' => self::UUID, 'version' => 1]);
        $valid = ['type' => 'threat', 'sourceIdentity' => self::UUID];
        $catalog->replaceTemplatePackage(1, [$valid]);
        try {
            $catalog->replaceTemplatePackage(1, [$valid, array_replace($valid, [
                'type' => $type,
            ])]);
            self::fail('Unsupported string values must be rejected before replacing the package.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Invalid template package source type.', $exception->getMessage());
        }
        self::assertSame(['threat'], array_column($catalog->findTemplatePackage(1), 'type'));
    }

    public function unsupportedPackageValues(): array
    {
        return [
            ['recommendation'],
            ['recommendation-set'],
            ['users'],
            [str_repeat('x', 31)],
        ];
    }

    private function tables(): array
    {
        require_once dirname(__DIR__, 3) . '/Fixture/ScenarioPackageDatabase.php';
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        \createScenarioPackageDatabase($db);
        $em = $this->createMock(EntityManager::class);
        $em->method('getConnection')->willReturn($db);

        return [$db, new CatalogTable($em), new PackageSourceTable($em)];
    }
}
