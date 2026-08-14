<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;

class AddRiskReviewMetadata extends AbstractMigration
{
    public function up(): void
    {
        $this->table('anr_reassessment_triggers')
            ->addColumn('monitoring_approach', 'text', ['null' => true, 'after' => 'description'])
            ->update();

        $defaultMonitoringApproaches = [
            1 => [
                'fr' => 'Surveiller les demandes de changement, les mises en production, les revues d’architecture et l’inventaire des actifs.',
                'en' => 'Monitor change requests, production releases, architecture reviews, and the asset inventory.',
                'de' => 'Änderungsanträge, Produktivsetzungen, Architekturprüfungen und das Asset-Inventar überwachen.',
                'nl' => 'Wijzigingsverzoeken, productiereleases, architectuurbeoordelingen en de inventaris van activa opvolgen.',
            ],
            2 => [
                'fr' => 'Surveiller les scans de vulnérabilités, les bulletins de sécurité, les avis CERT et les alertes des éditeurs/fournisseurs.',
                'en' => 'Monitor vulnerability scans, security bulletins, CERT advisories, and vendor or supplier alerts.',
                'de' => 'Schwachstellenscans, Sicherheitsbulletins, CERT-Hinweise und Hersteller- oder Lieferantenwarnungen überwachen.',
                'nl' => 'Kwetsbaarheidsscans, beveiligingsbulletins, CERT-adviezen en waarschuwingen van leveranciers opvolgen.',
            ],
            3 => [
                'fr' => 'Surveiller les alertes SOC, les tickets d’incident, les journaux de sécurité et les rapports post-incident.',
                'en' => 'Monitor SOC alerts, incident tickets, security logs, and post-incident reports.',
                'de' => 'SOC-Warnungen, Incident-Tickets, Sicherheitsprotokolle und Berichte nach Vorfällen überwachen.',
                'nl' => 'SOC-waarschuwingen, incidenttickets, beveiligingslogboeken en post-incidentrapporten opvolgen.',
            ],
            4 => [
                'fr' => 'Surveiller les évolutions réglementaires, les analyses juridiques, les obligations contractuelles et les communications des autorités.',
                'en' => 'Monitor regulatory updates, legal analyses, contractual obligations, and communications from authorities.',
                'de' => 'Regulatorische Aktualisierungen, juristische Analysen, vertragliche Verpflichtungen und Mitteilungen von Behörden überwachen.',
                'nl' => 'Regelgevende updates, juridische analyses, contractuele verplichtingen en communicatie van autoriteiten opvolgen.',
            ],
            5 => [
                'fr' => 'Surveiller les évaluations fournisseurs, les SLA, les notifications de service et les incidents affectant les tiers critiques.',
                'en' => 'Monitor supplier assessments, SLAs, service notifications, and incidents affecting critical third parties.',
                'de' => 'Lieferantenbewertungen, SLAs, Servicebenachrichtigungen und Vorfälle bei kritischen Dritten überwachen.',
                'nl' => 'Leveranciersbeoordelingen, SLA’s, serviceberichten en incidenten bij kritieke derden opvolgen.',
            ],
            6 => [
                'fr' => 'Surveiller les changements d’organisation, les revues de processus, les mouvements d’effectifs et les mises à jour de responsabilités.',
                'en' => 'Monitor organizational changes, process reviews, staffing changes, and responsibility updates.',
                'de' => 'Organisationsänderungen, Prozessprüfungen, Personaländerungen und Aktualisierungen von Verantwortlichkeiten überwachen.',
                'nl' => 'Organisatiewijzigingen, procesbeoordelingen, personeelswijzigingen en updates van verantwoordelijkheden opvolgen.',
            ],
            7 => [
                'fr' => 'Surveiller les constats d’audit, les plans d’action, les résultats d’évaluation et les contrôles non conformes.',
                'en' => 'Monitor audit findings, action plans, assessment results, and non-compliant controls.',
                'de' => 'Auditfeststellungen, Maßnahmenpläne, Bewertungsergebnisse und nicht konforme Kontrollen überwachen.',
                'nl' => 'Auditbevindingen, actieplannen, beoordelingsresultaten en niet-conforme controles opvolgen.',
            ],
            8 => [
                'fr' => 'Surveiller le renseignement sur les menaces, les flux externes, les rapports sectoriels et les campagnes actives pertinentes.',
                'en' => 'Monitor threat intelligence, external feeds, sector reports, and relevant active campaigns.',
                'de' => 'Bedrohungsinformationen, externe Feeds, Branchenberichte und relevante aktive Kampagnen überwachen.',
                'nl' => 'Threat intelligence, externe feeds, sectorrapporten en relevante actieve campagnes opvolgen.',
            ],
            9 => [
                'fr' => 'Surveiller les projets d’adoption technologique, les dossiers d’architecture, les demandes d’investissement et les essais pilotes.',
                'en' => 'Monitor technology adoption projects, architecture dossiers, investment requests, and pilot initiatives.',
                'de' => 'Technologieeinführungsprojekte, Architekturunterlagen, Investitionsanträge und Pilotinitiativen überwachen.',
                'nl' => 'Projecten voor technologische invoering, architectuurdossiers, investeringsaanvragen en proefinitiatieven opvolgen.',
            ],
            10 => [
                'fr' => 'Surveiller le calendrier de revue, les échéances de gouvernance, les rappels de conformité et les plans de contrôle périodiques.',
                'en' => 'Monitor the review schedule, governance deadlines, compliance reminders, and periodic control plans.',
                'de' => 'Den Überprüfungsplan, Governance-Fristen, Compliance-Erinnerungen und regelmäßige Kontrollpläne überwachen.',
                'nl' => 'De beoordelingsplanning, governance-deadlines, complianceherinneringen en periodieke controleplannen opvolgen.',
            ],
        ];

        foreach ($defaultMonitoringApproaches as $position => $monitoringApproaches) {
            $escapedMonitoringApproach = addslashes(json_encode($monitoringApproaches, JSON_THROW_ON_ERROR));
            $this->execute(
                "UPDATE `anr_reassessment_triggers`
                    SET `monitoring_approach` = '{$escapedMonitoringApproach}'
                  WHERE `position` = {$position}
                    AND `monitoring_approach` IS NULL"
            );
        }
    }

    public function down(): void
    {
        $this->table('anr_reassessment_triggers')
            ->removeColumn('monitoring_approach')
            ->update();
    }
}
