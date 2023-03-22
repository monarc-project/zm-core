<?php

use Phinx\Migration\AbstractMigration;
use Ramsey\Uuid\Uuid;

class AddObjectsUuid extends AbstractMigration
{
    public function change()
    {
        //uuid for asssets

        $data = [
            "96e69b41-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023f34-44d1-11e9-a78c-0800277f0571",
                "name1" => "Messagerie (+)",
                "name2" => "Mailbox (+)",
                "name3" => "Postfach (+)",
                "name4" => "E-mail (+)",
            ],
            "96e69b7e-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023f3f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Portail externe (+)",
                "name2" => "External portal (+)",
                "name3" => "Externes Portal (+)",
                "name4" => "Externe portal (+)",
            ],
            "96e69b99-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c5d-44d1-11e9-a78c-0800277f0571",
                "name1" => "Bâtiment (+)",
                "name2" => "Building (+)",
                "name3" => "Gebäude (+)",
                "name4" => "Gebouw (+)",
            ],
            "96e69bc6-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c5d-44d1-11e9-a78c-0800277f0571",
                "name1" => "Locaux (+)",
                "name2" => "Premises (+)",
                "name3" => "Geschäftsräume (+)",
                "name4" => "Lokalen (+)",
            ],
            "96e69c0c-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023ed7-44d1-11e9-a78c-0800277f0571",
                "name1" => "Réseau et commmunications (+)",
                "name2" => "Network and Communication (+)",
                "name3" => "Netzwerk und Kommunikation (+)",
                "name4" => "Netwerk en communicatie (+)",
            ],
            "96e69c49-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023eb7-44d1-11e9-a78c-0800277f0571",
                "name1" => "Utilisateur (+)",
                "name2" => "Users (+)",
                "name3" => "Benutzer (+)",
                "name4" => "Gebruiker (+)",
            ],
            "96e69c5b-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e90-44d1-11e9-a78c-0800277f0571",
                "name1" => "Développeur (+)",
                "name2" => "Software developer (+)",
                "name3" => "Softwareentwickler (+)",
                "name4" => "Ontwikkelaar (+)",
            ],
            "96e69c87-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023ea9-44d1-11e9-a78c-0800277f0571",
                "name1" => "Administrateur IT (+)",
                "name2" => "IT administrator (+)",
                "name3" => "IT-Administrator (+)",
                "name4" => "IT-administrator (+)",
            ],
            "96e69c97-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023d89-44d1-11e9-a78c-0800277f0571",
                "name1" => "Organisation de l’organisme (+)",
                "name2" => "Company organization (+)",
                "name3" => "Unternehmensorganisation (+)",
                "name4" => "Organisatie van het organisme (+)",
            ],
            "96e69ccd-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023d64-44d1-11e9-a78c-0800277f0571",
                "name1" => "Fournisseur (+)",
                "name2" => "Supplier (+)",
                "name3" => "Lieferanten (+)",
                "name4" => "Leverancier (+)",
            ],
            "96e69cdd-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023d29-44d1-11e9-a78c-0800277f0571",
                "name1" => "Matériel transportable (+)",
                "name2" => "Portable equipment (+)",
                "name3" => "Ortsveränderliche Betriebsmittel (+)",
                "name4" => "Transporteerbare hardware (+)",
            ],
            "96e69d16-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023d19-44d1-11e9-a78c-0800277f0571",
                "name1" => "Matériel fixe (+)",
                "name2" => "Fixed equipment (+)",
                "name3" => "Ortsfeste Betriebsmittel (+)",
                "name4" => "Vaste hardware (+)",
            ],
            "96e69d43-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023d0a-44d1-11e9-a78c-0800277f0571",
                "name1" => "Logiciel standard (+)",
                "name2" => "Standard software (+)",
                "name3" => "Standardsoftware (+)",
                "name4" => "Standaardsoftware (+)",
            ],
            "96e69d72-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023cfb-44d1-11e9-a78c-0800277f0571",
                "name1" => "Logiciel d’administration (+)",
                "name2" => "Administration software (+)",
                "name3" => "Verwaltungssoftware (+)",
                "name4" => "Administratieve software (+)",
            ],
            "96e69da5-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023ceb-44d1-11e9-a78c-0800277f0571",
                "name1" => "Système d'exploitation (+)",
                "name2" => "Operation System (+)",
                "name3" => "Betriebssystem (+)",
                "name4" => "Besturingssysteem (+)",
            ],
            "96e69dd2-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023cb8-44d1-11e9-a78c-0800277f0571",
                "name1" => "Application métier (+)",
                "name2" => "Business application (+)",
                "name3" => "Geschäftsanwendung (+)",
                "name4" => "Vaktoepassing (+)",
            ],
            "96e69dfe-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e65-44d1-11e9-a78c-0800277f0571",
                "name1" => "Gestion serveurs",
                "name2" => "Server management",
                "name3" => "Servermanagement",
                "name4" => "Serverbeheer",
            ],
            "96e69e0d-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e4b-44d1-11e9-a78c-0800277f0571",
                "name1" => "Réseau & télécom",
                "name2" => "Network and Telecom",
                "name3" => "Netzwerk und Telekommunikation",
                "name4" => "Netwerk & telecom",
            ],
            "96e69e3b-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e6f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Employé",
                "name2" => "Employee",
                "name3" => "Mitarbeiter",
                "name4" => "Werknemer",
            ],
            "96e69e5c-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e6f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Employés",
                "name2" => "Employees",
                "name3" => "Mitarbeiter",
                "name4" => "Werknemers",
            ],
            "96e69e7e-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e2b-44d1-11e9-a78c-0800277f0571",
                "name1" => "Organisation informatique",
                "name2" => "IT organization",
                "name3" => "IT-Organisation",
                "name4" => "IT-organisatie",
            ],
            "96e69e8e-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023db5-44d1-11e9-a78c-0800277f0571",
                "name1" => "Développements logiciels",
                "name2" => "Software development",
                "name3" => "Softwareentwicklung",
                "name4" => "Softwareontwikkelingen",
            ],
            "96e69f00-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023dd3-44d1-11e9-a78c-0800277f0571",
                "name1" => "Logiciel spécifique",
                "name2" => "Specific software",
                "name3" => "Spezifische Software",
                "name4" => "Specifieke software",
            ],
            "96e69f2b-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023de2-44d1-11e9-a78c-0800277f0571",
                "name1" => "Maintenance logiciel spécifique",
                "name2" => "Specific software maintenance",
                "name3" => "Spezifische Softwarewartung",
                "name4" => "Onderhoud specifieke software",
            ],
            "96e69f62-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023dc4-44d1-11e9-a78c-0800277f0571",
                "name1" => "Documents physiques",
                "name2" => "Physical documents",
                "name3" => "Physische Dokumente",
                "name4" => "Fysieke documenten",
            ],
            "96e69f71-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023da7-44d1-11e9-a78c-0800277f0571",
                "name1" => "Locaux de la société",
                "name2" => "Company premises",
                "name3" => "Firmengelände",
                "name4" => "Lokalen van de firma",
            ],
            "96e69f98-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023da7-44d1-11e9-a78c-0800277f0571",
                "name1" => "Salle archive",
                "name2" => "Archive room",
                "name3" => "Archivraum",
                "name4" => "Archieflokaal",
            ],
            "96e69fa8-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e59-44d1-11e9-a78c-0800277f0571",
                "name1" => "Salle informatique ",
                "name2" => "IT room",
                "name3" => "IT-Raum",
                "name4" => "Informaticalokaal",
            ],
            "96e69fb8-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023d98-44d1-11e9-a78c-0800277f0571",
                "name1" => "Gestion des backups",
                "name2" => "Backup management",
                "name3" => "Datensicherungsmanagement",
                "name4" => "Beheer van de back-ups",
            ],
            "96e69fc9-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023ca5-44d1-11e9-a78c-0800277f0571",
                "name1" => "Information",
                "name2" => "Data",
                "name3" => "Daten",
                "name4" => "Informatie",
            ],
            "96e69fd9-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023ca5-44d1-11e9-a78c-0800277f0571",
                "name1" => "Base de données métier",
                "name2" => "Business database",
                "name3" => "Geschäftsdatenbank",
                "name4" => "Vakdatabase",
            ],
            "96e6a01f-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023ee2-44d1-11e9-a78c-0800277f0571",
                "name1" => "Service",
                "name2" => "Department",
                "name3" => "Abteilung",
                "name4" => "Service",
            ],
            "96e6a02f-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e01-44d1-11e9-a78c-0800277f0571",
                "name1" => "Imprimante",
                "name2" => "Printer",
                "name3" => "Drucker",
                "name4" => "Printer",
            ],
            "96e6a03f-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023df2-44d1-11e9-a78c-0800277f0571",
                "name1" => "Smartphone",
                "name2" => "Smartphone",
                "name3" => "Smartphone",
                "name4" => "Smartphone",
            ],
            "96e6a04f-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023df2-44d1-11e9-a78c-0800277f0571",
                "name1" => "Ordinateur portable",
                "name2" => "Laptop",
                "name3" => "Laptop",
                "name4" => "Draagbare computer",
            ],
            "96e6a05f-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e3c-44d1-11e9-a78c-0800277f0571",
                "name1" => "Postes de travail utilisateurs",
                "name2" => "User workstations",
                "name3" => "Benutzer-Arbeitsstationen",
                "name4" => "Gebruikerswerkstations",
            ],
            "96e6a06f-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e3c-44d1-11e9-a78c-0800277f0571",
                "name1" => "Postes de travail admin",
                "name2" => "Administrator workstations",
                "name3" => "Administrator-Arbeitsstationen",
                "name4" => "Administratorwerkstations",
            ],
            "96e6a081-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e3c-44d1-11e9-a78c-0800277f0571",
                "name1" => "Ordinateur de bureau",
                "name2" => "Desktop Computer",
                "name3" => "Desktop-Computer",
                "name4" => "Desktopcomputer",
            ],
            "96e6a091-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Back Office",
                "name2" => "Back Office",
                "name3" => "Backoffice",
                "name4" => "Back Office",
            ],
            "96e6a0a0-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Front Office",
                "name2" => "Front Office",
                "name3" => "Frontoffice",
                "name4" => "Front Office",
            ],
            "96e6a0d0-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023da7-44d1-11e9-a78c-0800277f0571",
                "name1" => "Bâtiment",
                "name2" => "Building",
                "name3" => "Gebäude",
                "name4" => "Gebouw",
            ],
            "96e6a0f2-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023e6f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Administrateur système",
                "name2" => "System administrator",
                "name3" => "Systemadministrator",
                "name4" => "Systeembeheerder",
            ],
            "96e6a11a-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023da7-44d1-11e9-a78c-0800277f0571",
                "name1" => "Bureau du service",
                "name2" => "Service office",
                "name3" => "Servicestelle",
                "name4" => "Servicekantoor",
            ],
            "96e6a12a-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Obligations légales GDPR",
                "name2" => "GDPR legal obligations",
                "name3" => "DSGVO gesetzliche Verpflichtungen",
                "name4" => "Wettelijke verplichtingen AVG",
            ],
            "96e6a15c-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Gouvernance",
                "name2" => "Governance",
                "name3" => "Führung",
                "name4" => "Bestuur",
            ],
            "96e6a173-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Principes relatifs au traitement",
                "name2" => "Principles relating to processing of personal data",
                "name3" => "Prinzipien bezüglich der Verarbeitung personenbezogener Daten",
                "name4" => "Principes i.v.m. de verwerking",
            ],
            "96e6a1a0-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Licéité et légitimé",
                "name2" => "Lawfulness and legitimity",
                "name3" => "Rechtmäßigkeit und Legitimität",
                "name4" => "Rechtmatig en gelegitimeerd",
            ],
            "96e6a1d4-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Consentement",
                "name2" => "Consent",
                "name3" => "Einwilligung",
                "name4" => "Toestemming",
            ],
            "96e6a1e4-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droits de la personne",
                "name2" => "Rights of the data subject",
                "name3" => "Rechte der Betroffenen",
                "name4" => "Mensenrechten",
            ],
            "96e6a214-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droit à l'information",
                "name2" => "Right to information",
                "name3" => "Recht auf Unterrichtung",
                "name4" => "Informatierecht",
            ],
            "96e6a244-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droit d'accès",
                "name2" => "Right of access",
                "name3" => "Recht auf Auskunft",
                "name4" => "Toegangsrecht",
            ],
            "96e6a269-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droit de rectification",
                "name2" => "Right to rectification",
                "name3" => "Recht auf Berichtigung",
                "name4" => "Rechtzettingsrecht",
            ],
            "96e6a279-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droit à l'effacement",
                "name2" => "Right to erasure",
                "name3" => "Recht auf Löschung",
                "name4" => "Schrappingsrecht",
            ],
            "96e6a2a3-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droit à la limitation du traitement",
                "name2" => "Right to restriction of processing",
                "name3" => "Recht auf Einschränkung der Verarbeitung",
                "name4" => "Recht op de beperking van de verwerking",
            ],
            "96e6a2e2-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droit à la portabilité",
                "name2" => "Right to portability",
                "name3" => "Recht auf Datenübertragbarkeit",
                "name4" => "Recht op overdraagbaarheid",
            ],
            "96e6a312-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Droit d'opposition",
                "name2" => "Right to object",
                "name3" => "Recht auf Widerspruch",
                "name4" => "Bezwaarrecht",
            ],
            "96e6a322-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "DPO",
                "name2" => "DPO",
                "name3" => "DSB",
                "name4" => "FGB",
            ],
            "96e6a336-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Destinataires",
                "name2" => "Recipients",
                "name3" => "Empfänger",
                "name4" => "Geadresseerden",
            ],
            "96e6a370-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Sous-traitant",
                "name2" => "Processor",
                "name3" => "Auftragsverarbeiter",
                "name4" => "Onderaannemer",
            ],
            "96e6a387-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Sous-traitance directe",
                "name2" => "Direct subcontracting",
                "name3" => "Direkte Untervergabe",
                "name4" => "Rechtstreekse onderaanneming",
            ],
            "96e6a39d-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Sous-traitance en cascade",
                "name2" => "Subcontracting in cascade",
                "name3" => "Untervergabe in Kaskade",
                "name4" => "Meervoudige onderaanneming",
            ],
            "96e6a3b4-513c-11e9-ac8c-0800277f0571" => [
                "asset" => "d2023c8f-44d1-11e9-a78c-0800277f0571",
                "name1" => "Transfert hors UE",
                "name2" => "Transfer outside EU",
                "name3" => "Übertragung außerhalb EU",
                "name4" => "Doorgifte buiten EU",
            ],
        ];
        // Migration for table objects -- Modify the data
        $table = $this->table('objects');
        $table
            ->addColumn('uuid', 'uuid', ['after' => 'id'])
            ->addIndex(['uuid'])
            ->update();
        foreach ($data as $key => $value) { //fill the uuid only for objects created by cases
            $this->execute(
                'UPDATE objects SET uuid =' . '"' . $key . '"' . ' WHERE name1 =' . '"' . $value['name1'] . '" AND asset_id =' . '"' . $value['asset'] . '"'
            );
        }
        $unUUIDpdo = $this->query('select uuid,id from objects' . ' WHERE uuid =' . '"' . '"');
        $unUUIDrows = $unUUIDpdo->fetchAll();

        foreach ($unUUIDrows as $key => $value) {
            $this->execute(
                'UPDATE objects SET uuid =' . '"' . Uuid::uuid4() . '"' . ' WHERE id =' . $value['id']
            ); //manage objects which are not in common
        }

        $table = $this->table('anrs_objects');
        $table->dropForeignKey('object_id')
            ->addColumn('object_uuid', 'uuid', ['after' => 'id'])
            ->update();
        $this->execute('UPDATE anrs_objects A,objects B SET A.object_uuid = B.uuid where B.id=A.object_id');
        $table->removeColumn('object_id')->save();
        $table->renameColumn('object_uuid', 'object_id')->save();

        $table = $this->table('instances');
        $table->dropForeignKey('object_id')
            ->addColumn('object_uuid', 'uuid', ['after' => 'id', 'null' => true])
            ->update();
        $this->execute('UPDATE instances A,objects B SET A.object_uuid = B.uuid where B.id=A.object_id');
        $table->removeColumn('object_id')->save();
        $table->renameColumn('object_uuid', 'object_id')->save();

        $table = $this->table('instances_risks_op');
        $table->dropForeignKey('object_id')
            ->addColumn('object_uuid', 'uuid', ['after' => 'id', 'null' => true])
            ->update();
        $this->execute('UPDATE instances_risks_op A,objects B SET A.object_uuid = B.uuid where B.id=A.object_id');
        $table->removeColumn('object_id')->save();
        $table->renameColumn('object_uuid', 'object_id')->save();

        $table = $this->table('instances_consequences');
        $table->dropForeignKey('object_id')
            ->addColumn('object_uuid', 'uuid', ['after' => 'id', 'null' => true])
            ->update();
        $this->execute('UPDATE instances_consequences A,objects B SET A.object_uuid = B.uuid where B.id=A.object_id');
        $table->removeColumn('object_id')->save();
        $table->renameColumn('object_uuid', 'object_id')->save();

        $table = $this->table('objects_objects'); //set the stufff for objects_objects
        $table->dropForeignKey('father_id')
            ->dropForeignKey('child_id')
            ->addColumn('father_uuid', 'uuid', ['after' => 'id', 'null' => true])
            ->addColumn('child_uuid', 'uuid', ['after' => 'id', 'null' => true])
            ->update();
        $this->execute(
            'UPDATE objects_objects A,objects B SET A.father_uuid = B.uuid where B.id=A.father_id and A.father_id is not null'
        );
        $this->execute(
            'UPDATE objects_objects A,objects B SET A.child_uuid = B.uuid where B.id=A.child_id and A.child_id is not null'
        );
        $table->removeColumn('father_id')
            ->removeColumn('child_id')
            ->save();
        $table->renameColumn('father_uuid', 'father_id')
            ->renameColumn('child_uuid', 'child_id')
            ->update();

        $this->table('objects')->removeColumn('id')->update();
        $this->execute("ALTER TABLE objects ADD PRIMARY KEY uuid_anr_id (uuid)");

        //manage Foreign key
        $table = $this->table('anrs_objects');
        $table->addForeignKey('object_id', 'objects', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->update();
        $table = $this->table('instances');
        $table->addForeignKey('object_id', 'objects', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->update();
        $table = $this->table('instances_risks_op');
        $table->addForeignKey('object_id', 'objects', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->update();
        $table = $this->table('objects_objects');
        $table->addForeignKey('father_id', 'objects', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->addForeignKey('child_id', 'objects', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->update();
    }
}
