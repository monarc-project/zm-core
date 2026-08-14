<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;

class AddReassessmentTriggers extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `anr_reassessment_triggers` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `trigger_type` text DEFAULT NULL,
                `description` text NOT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `position` int(11) NOT NULL DEFAULT 0,
                `creator` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `anr_reassessment_triggers_position_indx` (`position`),
                KEY `anr_reassessment_triggers_is_active_indx` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $defaultTriggers = [
            [
                'trigger_types' => [
                    'fr' => 'Changement système',
                    'en' => 'System change',
                    'de' => 'Systemänderung',
                    'nl' => 'Systeemwijziging',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer l’analyse après des changements importants des systèmes, de l’architecture ou des actifs critiques du périmètre.',
                    'en' => 'Reassess the analysis after significant changes to systems, architecture, or critical assets in scope.',
                    'de' => 'Die Analyse nach wesentlichen Änderungen an Systemen, Architektur oder kritischen Assets im Geltungsbereich neu bewerten.',
                    'nl' => 'Beoordeel de analyse opnieuw na belangrijke wijzigingen aan systemen, architectuur of kritieke activa binnen de scope.',
                ],
                'position' => 1,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Nouvelle vulnérabilité',
                    'en' => 'New vulnerability',
                    'de' => 'Neue Schwachstelle',
                    'nl' => 'Nieuwe kwetsbaarheid',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer lorsqu’une nouvelle vulnérabilité affecte des technologies, composants ou fournisseurs utilisés par les actifs du périmètre.',
                    'en' => 'Reassess when a new vulnerability affects technologies, components, or suppliers used by scoped assets.',
                    'de' => 'Neu bewerten, wenn eine neue Schwachstelle Technologien, Komponenten oder Lieferanten betrifft, die von den Assets im Geltungsbereich genutzt werden.',
                    'nl' => 'Beoordeel opnieuw wanneer een nieuwe kwetsbaarheid technologieën, componenten of leveranciers treft die door activa binnen de scope worden gebruikt.',
                ],
                'position' => 2,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Incident de sécurité',
                    'en' => 'Security incident',
                    'de' => 'Sicherheitsvorfall',
                    'nl' => 'Beveiligingsincident',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer après un incident de sécurité significatif, un quasi-incident ou une compromission confirmée affectant les actifs du périmètre.',
                    'en' => 'Reassess after a significant security incident, near miss, or confirmed compromise affecting scoped assets.',
                    'de' => 'Nach einem wesentlichen Sicherheitsvorfall, einem Beinahe-Vorfall oder einer bestätigten Kompromittierung von Assets im Geltungsbereich neu bewerten.',
                    'nl' => 'Beoordeel opnieuw na een belangrijk beveiligingsincident, bijna-incident of bevestigde compromittering die activa binnen de scope raakt.',
                ],
                'position' => 3,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Changement réglementaire',
                    'en' => 'Regulatory change',
                    'de' => 'Regulatorische Änderung',
                    'nl' => 'Regelgevingswijziging',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer lorsque des obligations légales, réglementaires, contractuelles ou de conformité pertinentes pour l’analyse évoluent.',
                    'en' => 'Reassess when legal, regulatory, contractual, or compliance obligations relevant to the analysis change.',
                    'de' => 'Neu bewerten, wenn sich für die Analyse relevante rechtliche, regulatorische, vertragliche oder Compliance-Verpflichtungen ändern.',
                    'nl' => 'Beoordeel opnieuw wanneer wettelijke, reglementaire, contractuele of complianceverplichtingen die relevant zijn voor de analyse veranderen.',
                ],
                'position' => 4,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Changement fournisseur',
                    'en' => 'Supplier change',
                    'de' => 'Lieferantenänderung',
                    'nl' => 'Leverancierswijziging',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer lorsqu’un fournisseur critique, un prestataire de services ou une dépendance externalisée change ou subit une perturbation.',
                    'en' => 'Reassess when a critical supplier, service provider, or outsourced dependency changes or experiences disruption.',
                    'de' => 'Neu bewerten, wenn sich ein kritischer Lieferant, Dienstleister oder eine ausgelagerte Abhängigkeit ändert oder gestört wird.',
                    'nl' => 'Beoordeel opnieuw wanneer een kritieke leverancier, dienstverlener of uitbestede afhankelijkheid verandert of een verstoring ondervindt.',
                ],
                'position' => 5,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Changement organisationnel',
                    'en' => 'Organizational change',
                    'de' => 'Organisatorische Änderung',
                    'nl' => 'Organisatorische wijziging',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer lorsque des changements majeurs d’organisation, de processus, d’effectifs ou de rôles affectent les responsabilités ou les opérations.',
                    'en' => 'Reassess when major organizational, process, staffing, or role changes affect responsibilities or operations.',
                    'de' => 'Neu bewerten, wenn wesentliche organisatorische, prozessuale, personelle oder Rollenänderungen Verantwortlichkeiten oder Abläufe beeinflussen.',
                    'nl' => 'Beoordeel opnieuw wanneer grote organisatorische wijzigingen, proceswijzigingen, personeelswijzigingen of rolwijzigingen verantwoordelijkheden of activiteiten beïnvloeden.',
                ],
                'position' => 6,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Constat d’audit',
                    'en' => 'Audit finding',
                    'de' => 'Auditfeststellung',
                    'nl' => 'Auditbevinding',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer après des constats d’audit, des résultats d’évaluation ou des lacunes de contrôle modifiant de manière significative le profil de risque.',
                    'en' => 'Reassess after audit findings, assessment results, or control gaps materially change the risk picture.',
                    'de' => 'Neu bewerten, wenn Prüfungsfeststellungen, Bewertungsergebnisse oder Kontrolllücken das Risikobild wesentlich verändern.',
                    'nl' => 'Beoordeel opnieuw wanneer auditbevindingen, beoordelingsresultaten of controlelacunes het risicobeeld wezenlijk veranderen.',
                ],
                'position' => 7,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Évolution du paysage des menaces',
                    'en' => 'Threat landscape change',
                    'de' => 'Änderung der Bedrohungslage',
                    'nl' => 'Wijziging in dreigingslandschap',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer lorsque le renseignement sur les menaces indique une évolution significative du paysage des menaces pour les actifs du périmètre.',
                    'en' => 'Reassess when threat intelligence indicates a meaningful change in the threat landscape for scoped assets.',
                    'de' => 'Neu bewerten, wenn Bedrohungsinformationen auf eine wesentliche Veränderung der Bedrohungslage für Assets im Geltungsbereich hinweisen.',
                    'nl' => 'Beoordeel opnieuw wanneer threat intelligence wijst op een betekenisvolle verandering in het dreigingslandschap voor activa binnen de scope.',
                ],
                'position' => 8,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Nouvelle technologie',
                    'en' => 'New technology',
                    'de' => 'Neue Technologie',
                    'nl' => 'Nieuwe technologie',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer avant d’adopter de nouvelles technologies ou d’introduire des capacités techniques significatives dans le périmètre.',
                    'en' => 'Reassess before adopting new technologies or introducing significant technical capabilities into scope.',
                    'de' => 'Vor der Einführung neuer Technologien oder wesentlicher technischer Fähigkeiten im Geltungsbereich neu bewerten.',
                    'nl' => 'Beoordeel opnieuw voordat nieuwe technologieën worden ingevoerd of belangrijke technische mogelijkheden binnen de scope worden toegevoegd.',
                ],
                'position' => 9,
            ],
            [
                'trigger_types' => [
                    'fr' => 'Revue périodique',
                    'en' => 'Periodic review',
                    'de' => 'Regelmäßige Überprüfung',
                    'nl' => 'Periodieke beoordeling',
                ],
                'descriptions' => [
                    'fr' => 'Réevaluer sur une base périodique planifiée pour confirmer que les hypothèses, contrôles et niveaux de risque restent valides.',
                    'en' => 'Reassess on a planned periodic basis to confirm that assumptions, controls, and risk levels remain valid.',
                    'de' => 'In geplanten regelmäßigen Abständen neu bewerten, um zu bestätigen, dass Annahmen, Kontrollen und Risikoniveaus weiterhin gültig sind.',
                    'nl' => 'Beoordeel opnieuw op geplande periodieke basis om te bevestigen dat aannames, beheersmaatregelen en risiconiveaus geldig blijven.',
                ],
                'position' => 10,
            ],
        ];
        foreach ($defaultTriggers as $trigger) {
            $escapedTriggerType = addslashes(json_encode($trigger['trigger_types'], JSON_THROW_ON_ERROR));
            $escapedDescription = addslashes(json_encode($trigger['descriptions'], JSON_THROW_ON_ERROR));
            $position = (int)$trigger['position'];
            $this->execute(
                "INSERT INTO `anr_reassessment_triggers`
                    (`trigger_type`, `description`, `is_active`, `position`, `creator`, `created_at`)
                VALUES
                    ('{$escapedTriggerType}', '{$escapedDescription}', 1, {$position}, 'Migration script', NOW());"
            );
        }
    }

    public function down(): void
    {
        $this->table('anr_reassessment_triggers')->drop()->save();
    }
}
