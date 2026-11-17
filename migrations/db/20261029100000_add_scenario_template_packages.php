<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;

final class AddScenarioTemplatePackages extends AbstractMigration
{
    public function up(): void
    {
        // Source identities intentionally survive source deletion. Only the
        // constrained registry resolves them; published content uses snapshots.
        $this->execute(<<<'SQL'
CREATE TABLE scenario_catalog_template_package_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    source_type VARCHAR(30) NOT NULL,
    source_identity VARCHAR(36) NOT NULL,
    guidance TEXT NULL,
    position INT UNSIGNED NOT NULL,
    UNIQUE KEY scenario_package_source (template_id, source_type, source_identity),
    UNIQUE KEY scenario_package_position (template_id, position),
    CONSTRAINT scenario_package_template_fk FOREIGN KEY (template_id)
        REFERENCES scenario_catalog_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
        );
        $this->execute(<<<'SQL'
CREATE TABLE scenario_catalog_template_package_snapshots (
    link_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    source_json LONGTEXT NOT NULL,
    checksum CHAR(64) NOT NULL,
    captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT scenario_package_snapshot_link_fk FOREIGN KEY (link_id)
        REFERENCES scenario_catalog_template_package_links(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
        );
    }

    public function down(): void
    {
        $this->execute('DROP TABLE scenario_catalog_template_package_snapshots');
        $this->execute('DROP TABLE scenario_catalog_template_package_links');
    }
}
