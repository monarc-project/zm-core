<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Doctrine\DBAL\Connection;

/** Minimal SQLite persistence fixture; production migration validation is MariaDB-only. */
function createScenarioPackageDatabase(Connection $db): void
{
    foreach ([
        'assets' => 'uuid TEXT PRIMARY KEY, label1 TEXT, description1 TEXT, status INT, code TEXT',
        'objects' => 'uuid TEXT PRIMARY KEY, asset_id TEXT, name1 TEXT, label1 TEXT',
        'threats' => 'uuid TEXT PRIMARY KEY, label1 TEXT, status INT',
        'vulnerabilities' => 'uuid TEXT PRIMARY KEY, label1 TEXT, status INT',
        'referentials' => 'uuid TEXT PRIMARY KEY, label1 TEXT',
        'measures' => 'uuid TEXT PRIMARY KEY, referential_uuid TEXT, label1 TEXT, status INT',
        'risk_sources' => 'id INT PRIMARY KEY, label TEXT, is_active INT',
        'scenario_catalog_templates' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, stable_uuid TEXT, version INT, revision INT DEFAULT 1, lifecycle TEXT DEFAULT "draft", semantic_version TEXT, provenance TEXT, author_id INT, assessment_guidance TEXT, reviewer_id INT, published_at TEXT, deprecated_at TEXT',
        'scenario_catalog_template_package_links' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, template_id INT REFERENCES scenario_catalog_templates(id), source_type TEXT, source_identity TEXT, guidance TEXT, position INT, UNIQUE(template_id, source_type, source_identity), UNIQUE(template_id, position)',
        'scenario_catalog_template_package_snapshots' => 'link_id INT PRIMARY KEY REFERENCES scenario_catalog_template_package_links(id), source_json TEXT, checksum TEXT, captured_at TEXT DEFAULT CURRENT_TIMESTAMP',
        'scenario_catalog_audit_events' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, resource_type TEXT, resource_uuid TEXT, resource_version INT, action TEXT, actor_id INT, correlation_id TEXT, payload_json TEXT',
        'scenario_catalog_template_translations' => 'template_id INT, locale TEXT, title TEXT, description TEXT',
        'scenario_catalog_references' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, resource_type TEXT, resource_id INT, reference_type TEXT, reference_value TEXT',
        'scenario_catalog_suggestions' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, resource_type TEXT, resource_id INT, suggestion_type TEXT, reference_type TEXT, reference_value TEXT, guidance TEXT',
        'scenario_catalog_tags' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT',
        'scenario_catalog_tag_links' => 'resource_type TEXT, resource_id INT, tag_id INT',
        'scenario_catalog_template_events' => 'template_id INT, event_id INT, position INT',
        'scenario_catalog_events' => 'id INT, stable_uuid TEXT, version INT, lifecycle TEXT',
        'scenario_catalog_template_risk_models' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, template_id INT, stable_uuid TEXT, title TEXT, description TEXT, position INT, revision INT DEFAULT 1',
        'scenario_catalog_event_translations' => 'event_id INT, locale TEXT, title TEXT',
        'scenario_catalog_template_model_events' => 'id INT, model_id INT, event_id INT, event_stable_uuid TEXT, event_version INT, event_title_snapshot TEXT, guidance TEXT, position INT',
        'scenario_catalog_releases' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT, resource_type TEXT, resource_uuid TEXT, resource_version INT, released_by INT',
    ] as $table => $columns) {
        $db->executeStatement("CREATE TABLE $table ($columns)");
    }
    $uuid = '00000000-0000-4000-8000-000000000001';
    $db->insert('assets', ['uuid' => $uuid, 'label1' => 'Asset type', 'status' => 1]);
    $db->insert('objects', ['uuid' => $uuid, 'asset_id' => $uuid, 'name1' => 'Library asset', 'label1' => 'Library asset description']);
    $db->insert('threats', ['uuid' => $uuid, 'label1' => 'Threat', 'status' => 1]);
    $db->insert('vulnerabilities', ['uuid' => $uuid, 'label1' => 'Vulnerability', 'status' => 1]);
    $db->insert('referentials', ['uuid' => $uuid, 'label1' => 'Framework']);
    $db->insert('measures', ['uuid' => $uuid, 'referential_uuid' => $uuid, 'label1' => 'Control', 'status' => 1]);
    $db->insert('risk_sources', ['id' => 1, 'label' => 'Risk source', 'is_active' => 1]);
}
