<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;

class AddRiskSources extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `risk_sources` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `label` text NOT NULL,
                `is_default` tinyint(1) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `creator` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `risk_sources_is_active_indx` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $defaultRiskSources = [
            [
                'fr' => 'Attaquant externe',
                'en' => 'External attacker',
                'de' => 'Externer Angreifer',
                'nl' => 'Externe aanvaller',
            ],
            [
                'fr' => 'Utilisateur interne malveillant',
                'en' => 'Internal malicious user',
                'de' => 'Interner böswilliger Benutzer',
                'nl' => 'Interne kwaadwillende gebruiker',
            ],
            [
                'fr' => 'Utilisateur interne accidentel',
                'en' => 'Internal accidental user',
                'de' => 'Interner versehentlicher Benutzer',
                'nl' => 'Interne onbedoelde gebruiker',
            ],
            [
                'fr' => 'Fournisseur / tiers',
                'en' => 'Supplier / third party',
                'de' => 'Lieferant / Drittpartei',
                'nl' => 'Leverancier / derde partij',
            ],
            [
                'fr' => 'Défaillance système',
                'en' => 'System failure',
                'de' => 'Systemausfall',
                'nl' => 'Systeemstoring',
            ],
            [
                'fr' => 'Défaut logiciel',
                'en' => 'Software defect',
                'de' => 'Softwarefehler',
                'nl' => 'Softwaredefect',
            ],
            [
                'fr' => 'Événement naturel',
                'en' => 'Natural event',
                'de' => 'Naturereignis',
                'nl' => 'Natuurgebeurtenis',
            ],
            [
                'fr' => 'Faiblesse organisationnelle ou processuelle',
                'en' => 'Organizational or process weakness',
                'de' => 'Organisatorische oder prozessuale Schwäche',
                'nl' => 'Organisatorische of procesmatige zwakte',
            ],
            [
                'fr' => 'Autre',
                'en' => 'Other',
                'de' => 'Sonstige',
                'nl' => 'Overige',
            ],
        ];
        foreach ($defaultRiskSources as $labels) {
            $escapedLabel = addslashes(json_encode($labels, JSON_THROW_ON_ERROR));
            $this->execute(
                "INSERT INTO `risk_sources` (`label`, `is_default`, `is_active`, `creator`, `created_at`) VALUES
                ('{$escapedLabel}', 1, 1, 'Migration script', NOW());"
            );
        }

        $this->table('instances_risks')
            ->addColumn('risk_source_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'asset_id'])
            ->addIndex(['risk_source_id'], ['name' => 'risk_source_id'])
            ->addForeignKey('risk_source_id', 'risk_sources', 'id', ['delete' => 'SET_NULL', 'update' => 'RESTRICT'])
            ->update();
    }

    public function down(): void
    {
        $this->table('instances_risks')
            ->dropForeignKey('risk_source_id')
            ->removeIndexByName('risk_source_id')
            ->removeColumn('risk_source_id')
            ->update();

        $this->table('risk_sources')->drop()->save();
    }
}
