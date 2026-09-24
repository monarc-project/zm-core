<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddScenarioCatalogReleaseUuid extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE scenario_catalog_releases ADD uuid CHAR(36) NULL AFTER id, ADD UNIQUE KEY scenario_catalog_releases_uuid (uuid)');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE scenario_catalog_releases DROP INDEX scenario_catalog_releases_uuid, DROP COLUMN uuid');
    }
}
