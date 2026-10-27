<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Scenario\Table;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Scenario\Table\CatalogTable;
use PHPUnit\Framework\TestCase;

/** @covers \Monarc\Core\Scenario\Table\CatalogTable */
final class CatalogTableTest extends TestCase
{
    public function testItReadsTemplateEventReferencesInTheirStoredPositionOrder(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->callback(
                    static fn (string $query): bool => str_contains($query, 'ORDER BY link.position')
                        && !str_contains($query, 'link.id')
                ),
                [42]
            )
            ->willReturn([
                ['stable_uuid' => '00000000-0000-4000-8000-000000000002', 'version' => '2'],
                ['stable_uuid' => '00000000-0000-4000-8000-000000000001', 'version' => '1'],
            ]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $table = new CatalogTable($entityManager);

        self::assertSame([
            ['stableUuid' => '00000000-0000-4000-8000-000000000002', 'version' => 2],
            ['stableUuid' => '00000000-0000-4000-8000-000000000001', 'version' => 1],
        ], $table->findTemplateEventReferences(42));
    }

    public function testItDoesNotWriteEventOnlyGuidanceToTemplateTranslations(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('delete')
            ->with('scenario_catalog_template_translations', ['template_id' => 42]);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                'scenario_catalog_template_translations',
                $this->callback(static function (array $fields): bool {
                    return $fields['template_id'] === 42
                        && $fields['locale'] === 'en'
                        && $fields['title'] === 'Supplier interruption'
                        && !array_key_exists('risk_source_guidance', $fields)
                        && !array_key_exists('cause_guidance', $fields)
                        && array_key_exists('consequence_guidance', $fields)
                        && array_key_exists('monitoring_guidance', $fields);
                })
            );

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $table = new CatalogTable($entityManager);
        $table->replaceTranslations('template', 42, [
            'en' => [
                'title' => 'Supplier interruption',
                'description' => 'Supplier service disruption.',
                'riskSourceGuidance' => null,
                'causeGuidance' => null,
                'consequenceGuidance' => null,
                'monitoringGuidance' => null,
            ],
        ]);
    }
}
