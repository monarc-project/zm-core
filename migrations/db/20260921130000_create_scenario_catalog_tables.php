<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Shared Scenario Library schema.
 *
 * The catalog is stored in monarc_common so FrontOffice can later read
 * published versions without copying them. Author, reviewer and audit actor
 * IDs reference BackOffice identities in monarc_master, so they deliberately
 * have no cross-database foreign-key constraint.
 */
final class CreateScenarioCatalogTables extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE scenario_catalog_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            stable_uuid CHAR(36) NOT NULL,
            version INT UNSIGNED NOT NULL,
            semantic_version VARCHAR(32) NOT NULL DEFAULT '1.0.0',
            lifecycle VARCHAR(16) NOT NULL DEFAULT 'draft',
            category VARCHAR(100) NOT NULL,
            provenance TEXT NOT NULL,
            guidance TEXT NULL,
            author_id INT UNSIGNED NOT NULL,
            reviewer_id INT UNSIGNED NULL,
            revision INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at DATETIME NULL,
            deprecated_at DATETIME NULL,
            UNIQUE KEY scenario_catalog_events_version (stable_uuid, version),
            KEY scenario_catalog_events_lifecycle (lifecycle)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_event_translations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            locale VARCHAR(10) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            risk_source_guidance TEXT NULL,
            cause_guidance TEXT NULL,
            monitoring_guidance TEXT NULL,
            UNIQUE KEY scenario_catalog_event_locale (event_id, locale),
            CONSTRAINT scenario_catalog_event_translation_fk
                FOREIGN KEY (event_id) REFERENCES scenario_catalog_events(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            stable_uuid CHAR(36) NOT NULL,
            version INT UNSIGNED NOT NULL,
            semantic_version VARCHAR(32) NOT NULL DEFAULT '1.0.0',
            lifecycle VARCHAR(16) NOT NULL DEFAULT 'draft',
            provenance TEXT NOT NULL,
            assessment_guidance TEXT NULL,
            author_id INT UNSIGNED NOT NULL,
            reviewer_id INT UNSIGNED NULL,
            revision INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at DATETIME NULL,
            deprecated_at DATETIME NULL,
            UNIQUE KEY scenario_catalog_templates_version (stable_uuid, version),
            KEY scenario_catalog_templates_lifecycle (lifecycle)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_template_translations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_id INT UNSIGNED NOT NULL,
            locale VARCHAR(10) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            consequence_guidance TEXT NULL,
            subject_guidance TEXT NULL,
            control_guidance TEXT NULL,
            monitoring_guidance TEXT NULL,
            UNIQUE KEY scenario_catalog_template_locale (template_id, locale),
            CONSTRAINT scenario_catalog_template_translation_fk
                FOREIGN KEY (template_id) REFERENCES scenario_catalog_templates(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_template_events (
            template_id INT UNSIGNED NOT NULL,
            event_id INT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL,
            PRIMARY KEY(template_id, event_id),
            CONSTRAINT scenario_catalog_template_event_template_fk
                FOREIGN KEY (template_id) REFERENCES scenario_catalog_templates(id) ON DELETE CASCADE,
            CONSTRAINT scenario_catalog_template_event_event_fk
                FOREIGN KEY (event_id) REFERENCES scenario_catalog_events(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_releases (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resource_type VARCHAR(16) NOT NULL,
            resource_uuid CHAR(36) NOT NULL,
            resource_version INT UNSIGNED NOT NULL,
            released_by INT UNSIGNED NOT NULL,
            released_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY scenario_catalog_release_version (resource_type, resource_uuid, resource_version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_suggestions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resource_type VARCHAR(16) NOT NULL,
            resource_id INT UNSIGNED NOT NULL,
            suggestion_type VARCHAR(32) NOT NULL,
            reference_type VARCHAR(32) NOT NULL,
            reference_value VARCHAR(255) NOT NULL,
            guidance TEXT NULL,
            KEY scenario_catalog_suggestions_resource (resource_type, resource_id),
            KEY scenario_catalog_suggestions_kind (suggestion_type, reference_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_tags (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_tag_links (
            tag_id INT UNSIGNED NOT NULL,
            resource_type VARCHAR(16) NOT NULL,
            resource_id INT UNSIGNED NOT NULL,
            PRIMARY KEY(tag_id, resource_type, resource_id),
            CONSTRAINT scenario_catalog_tag_links_tag_fk
                FOREIGN KEY (tag_id) REFERENCES scenario_catalog_tags(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_references (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resource_type VARCHAR(16) NOT NULL,
            resource_id INT UNSIGNED NOT NULL,
            reference_type VARCHAR(32) NOT NULL,
            reference_value VARCHAR(255) NOT NULL,
            KEY scenario_catalog_references_resource (resource_type, resource_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("CREATE TABLE scenario_catalog_audit_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            resource_type VARCHAR(16) NOT NULL,
            resource_uuid CHAR(36) NOT NULL,
            resource_version INT UNSIGNED NOT NULL,
            action VARCHAR(32) NOT NULL,
            actor_id INT UNSIGNED NOT NULL,
            correlation_id VARCHAR(128) NULL,
            payload_json JSON NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY scenario_catalog_audit_resource (resource_type, resource_uuid, resource_version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_audit_events');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_releases');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_references');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_suggestions');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_tag_links');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_tags');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_template_events');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_template_translations');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_event_translations');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_templates');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_events');
    }
}
