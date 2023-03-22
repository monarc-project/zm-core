<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;


class AddSoaObjects extends AbstractMigration
{
    public function change()
    {
      // Migration for table Soa
      $table = $this->table('soa');
      $table
        //  ->addColumn('id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('reference', 'string', array('null' => true, 'limit' => 255))
        //  ->addColumn('control', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('requirement', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('justification', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('evidences', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('actions', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('compliance', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('measure_id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('control1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('control2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('control3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('control4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addIndex(array('measure_id'))

        //  ->addIndex(array(''))
          ->create();
      $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

      // Migration for table category
      $table = $this->table('soacategory');
      $table
      //  ->addColumn('id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('reference', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('label1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('label2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('label3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('label4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
          ->addColumn('status', 'integer', array('null' => true, 'default' => '1', 'limit' => 11))
          ->create();
      $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();
      $this->table('measures')
      ->addColumn('soacategory_id', 'integer',  array('null' => true, 'default' => '15',  'signed' => false))
      ->save();

      //set the default iso27002 categories
      $this->query('INSERT INTO soacategory (reference,label1, label2, label3,label4)values
      ("5","Politiques de sécurité de l\'information","Information security policies","Informationssicherheitspolitik","Informatiebeveiligingsbeleid"),
      ("6","Organisation de la sécurité de l\'information","Organization of information security","Organisation der Informationssicherheit","Organiseren van informatiebeveiliging"),
      ("7","La sécurité des ressources humaines","Human resource security","Personalsicherheit","Veilig personeel"),
      ("8","Gestion des actifs","Asset management","Asset Management","Beheer van bedrijfsmiddelen"),
      ("9","Contrôle d\'accès","Access control","Zugriffskontrolle","Toegangsbeveiliging"),
      ("10","Cryptographie","Cryptography","Kryptografie","Cryptografie"),
      ("11","Sécurité physique et environnementale","Physical and environmental security","Physische und Umgebungssicherheit","Fysieke beveiliging en beveiliging van de omgeving"),
      ("12","Sécurité liée à l\'exploitation","Operations security","Betriebssicherheit","Beveiliging bedrijfsvoering"),
      ("13","Sécurité des communications","Communications security","Kommunikationssicherheit","Communicatiebeveiliging"),
      ("14","Acquisition, développement et maintenance des systèmes d\'information","System acquisition, development and maintenance","Systemerwerb, Entwicklung und Wartung","Acquisitie, ontwikkeling en onderhoud van informatiesystemen"),
      ("15","Relations avec le fournisseurs","Supplier relationships","Lieferantenbeziehungen","Leveranciersrelaties"),
      ("16","Gestion des incidents liés à la sécurité de l\'information","information security incident management","Informationssicherheits-Störfallmanagement","Beheer van informatiebeveiligingsincidenten"),
      ("17","Aspects de la sécurité de l\'information dans la gestion de la continuité de l\'activité","Information security aspects of business continuity management","Informationssicherheitsaspekte des betrieblichen Kontinuitätsmanagement","Informatiebeveiligingsaspecten van bedrijfscontinuïteitsbeheer"),
      ("18","Conformité","Compliance","Konformität","Naleving");');
      $this->execute('UPDATE measures m SET m.soacategory_id= (SELECT id FROM soacategory c WHERE m.code LIKE concat(c.reference ,".","%"));');
    }
}
