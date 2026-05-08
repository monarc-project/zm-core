<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Ramsey\Uuid\Uuid;
use Phinx\Migration\AbstractMigration;

class AddRiskSources extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `risk_sources` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `label` varchar(255) NOT NULL,
                `label_translation_key` varchar(255) NOT NULL DEFAULT \'\',
                `is_default` tinyint(1) NOT NULL DEFAULT 0,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `creator` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `risk_sources_is_active_indx` (`is_active`),
                UNIQUE `risk_sources_label_unq` (`label`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $defaultRiskSources = [
            [
                'fr' => 'Attaquant externe',
                'en' => 'External attacker',
                'de' => 'Externer Angreifer',
                'nl' => 'Externe aanvaller',
                'pt' => 'Atacante externo',
            ],
            [
                'fr' => 'Utilisateur interne malveillant',
                'en' => 'Internal malicious user',
                'de' => 'Interner böswilliger Benutzer',
                'nl' => 'Interne kwaadwillende gebruiker',
                'pt' => 'Utilizador interno malicioso',
            ],
            [
                'fr' => 'Utilisateur interne accidentel',
                'en' => 'Internal accidental user',
                'de' => 'Interner versehentlicher Benutzer',
                'nl' => 'Interne onbedoelde gebruiker',
                'pt' => 'Utilizador interno acidental',
            ],
            [
                'fr' => 'Fournisseur / tiers',
                'en' => 'Supplier / third party',
                'de' => 'Lieferant / Drittpartei',
                'nl' => 'Leverancier / derde partij',
                'pt' => 'Fornecedor / terceiro',
            ],
            [
                'fr' => 'Défaillance système',
                'en' => 'System failure',
                'de' => 'Systemausfall',
                'nl' => 'Systeemstoring',
                'pt' => 'Falha do sistema',
            ],
            [
                'fr' => 'Défaut logiciel',
                'en' => 'Software defect',
                'de' => 'Softwarefehler',
                'nl' => 'Softwaredefect',
                'pt' => 'Defeito de software',
            ],
            [
                'fr' => 'Événement naturel',
                'en' => 'Natural event',
                'de' => 'Naturereignis',
                'nl' => 'Natuurgebeurtenis',
                'pt' => 'Evento natural',
            ],
            [
                'fr' => 'Faiblesse organisationnelle ou processuelle',
                'en' => 'Organizational or process weakness',
                'de' => 'Organisatorische oder prozessuale Schwäche',
                'nl' => 'Organisatorische of procesmatige zwakte',
                'pt' => 'Fraqueza organizacional ou de processo',
            ],
            [
                'fr' => 'Autre',
                'en' => 'Other',
                'de' => 'Sonstige',
                'nl' => 'Overige',
                'pt' => 'Outro',
            ],
        ];
        $languages = $this->getLanguageCodes();

        foreach ($defaultRiskSources as $labels) {
            $canonicalLabel = $labels['en'];
            $escapedLabel = addslashes($canonicalLabel);
            $translationKey = (string)Uuid::uuid4();
            $escapedTranslationKey = addslashes($translationKey);
            $this->execute(
                "INSERT INTO `risk_sources` (`label`, `label_translation_key`, `is_default`, `is_active`, `creator`, `created_at`) VALUES
                ('{$escapedLabel}', '{$escapedTranslationKey}', 1, 1, 'Migration script', NOW());"
            );

            foreach ($languages as $language) {
                if (isset($labels[$language])) {
                    $escapedValue = addslashes($labels[$language]);
                    $this->execute(
                        "INSERT INTO `translations` (`anr_id`, `type`, `translation_key`, `lang`, `value`, `creator`, `created_at`) VALUES
                        (NULL, 'risk-source', '{$escapedTranslationKey}', '{$language}', '{$escapedValue}', 'Migration script', NOW());"
                    );
                }
            }
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

        $this->execute("DELETE FROM `translations` WHERE `type` = 'risk-source';");
        $this->table('risk_sources')->drop()->save();
    }

    /**
     * Reads language codes from the application config (global.php + local.php), the same source
     * that phinx.php uses, so the list always reflects the instance's actual configuration.
     * Falls back to the four standard MONARC languages when the config files are not found.
     *
     * @return string[]
     */
    private function getLanguageCodes(): array
    {
        $config = [];
        $base = getcwd() . '/config/autoload/';
        foreach (['global.php', 'local.php'] as $file) {
            $path = $base . $file;
            if (file_exists($path)) {
                $config = array_replace_recursive($config, require $path);
            }
        }

        if (!empty($config['languages']) && is_array($config['languages'])) {
            return array_keys($config['languages']);
        }

        return ['fr', 'en', 'de', 'nl'];
    }
}
