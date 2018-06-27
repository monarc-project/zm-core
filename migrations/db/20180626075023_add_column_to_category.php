<?php

use Phinx\Migration\AbstractMigration;

class AddColumnToCategory extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
      $this->table('category')
      ->addColumn('reference', 'string', array('null' => true, 'limit' => 255))
      ->save();

      $this->query('
      delete from category;
      ');

      $this->query('
      INSERT INTO category (reference,label1, label2, label3,label4)
      VALUES ("5","Politiques de sécurité de l\'information","Information security policies","",""),
      ("6","Organisation de la sécurité de l\'information","Organization of information security ","",""),
      ("7","La sécurité des ressources humaines","Human resource security","",""),
      ("8","Gestion des actifs","Asset management","",""),
      ("9","Contrôle d\'accès","Access control","",""),
      ("10","Cryptographie","Cryptography","",""),
      ("11","Sécurité physique et environnementale","Physical and environmental security","",""),
      ("12","Sécurité liée à l\'exploitation","Operations security","",""),
      ("13","Sécurité des communications","Communications security","",""),
      ("14","Acquisition, développement et maintenance des systèmes d\'information","System acquisition, development and maintenance","",""),
      ("15","Relations avec le fournisseurs","Supplier relationships","",""),
      ("16","Gestion des incidents liés à la sécurité de l\'information","information security incident management","",""),
      ("17","Aspects de la sécurité de l\'information dans la gestion de la continuité de l\'activité","Information security aspects of business continuity management","",""),
      ("18","Conformité","Compliance","",""),
      ("19","aucune","none","","");
      ');


      $this->execute('
      UPDATE measures m SET m.category_id= (SELECT id FROM category c WHERE m.code LIKE concat(c.reference ,".","%"));

      ');







    }
}
