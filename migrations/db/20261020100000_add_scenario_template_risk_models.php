<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds the version-local risk-scenario model and available-event structure to
 * the shared Scenario template catalog. Catalog versions remain immutable once
 * published; FrontOffice snapshots this structure on instantiation.
 */
final class AddScenarioTemplateRiskModels extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
CREATE TABLE scenario_catalog_template_risk_models (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    template_id INT UNSIGNED NOT NULL,
    stable_uuid CHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    position INT UNSIGNED NOT NULL,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY scenario_catalog_template_risk_models_uuid (stable_uuid),
    UNIQUE KEY scenario_catalog_template_risk_models_position (template_id, position),
    KEY scenario_catalog_template_risk_models_template (template_id),
    CONSTRAINT scenario_catalog_template_risk_models_template_fk
        FOREIGN KEY (template_id) REFERENCES scenario_catalog_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        $this->execute(<<<'SQL'
CREATE TABLE scenario_catalog_template_model_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    model_id BIGINT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    event_stable_uuid CHAR(36) NOT NULL,
    event_version INT UNSIGNED NOT NULL,
    event_title_snapshot VARCHAR(255) NOT NULL,
    guidance TEXT NULL,
    position INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY scenario_catalog_template_model_events_link (model_id, event_id),
    UNIQUE KEY scenario_catalog_template_model_events_position (model_id, position),
    KEY scenario_catalog_template_model_events_event (event_id),
    CONSTRAINT scenario_catalog_template_model_events_model_fk
        FOREIGN KEY (model_id) REFERENCES scenario_catalog_template_risk_models(id) ON DELETE CASCADE,
    CONSTRAINT scenario_catalog_template_model_events_event_fk
        FOREIGN KEY (event_id) REFERENCES scenario_catalog_events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_template_model_events');
        $this->execute('DROP TABLE IF EXISTS scenario_catalog_template_risk_models');
    }
}
