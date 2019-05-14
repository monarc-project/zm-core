<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;
use Ramsey\Uuid\Uuid;

class AddRecommandations extends AbstractMigration
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
    //     // Migration for table recommandations
    //     $table = $this->table('recommandations');
    //     $table
    //         ->addColumn('uuid', 'uuid', array('after' => 'id'))
    //         ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
    //         ->addColumn('code', 'char', array('null' => true, 'limit' => 100))
    //         ->addColumn('description', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
    //         ->addColumn('importance', 'integer', array('null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY))
    //         ->addColumn('position', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
    //         ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
    //         ->addColumn('responsable', 'string', array('null' => true, 'limit' => 255))
    //         ->addColumn('duedate', 'datetime', array('null' => true))
    //         ->addColumn('counter_treated', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
    //         ->addColumn('original_code', 'char', array('null' => true, 'limit' => 100))
    //         ->addColumn('token_import', 'char', array('null' => true, 'limit' => 13))
    //         ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
    //         ->addColumn('created_at', 'datetime', array('null' => true))
    //         ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
    //         ->addColumn('updated_at', 'datetime', array('null' => true))
    //         ->addIndex(array('anr_id'))
    //         ->addIndex(array('anr_id','code'), array('name' => 'anr_id_2'))
    //         ->addIndex(array('uuid'))
    //         ->create();

    //     // Create default recommandations
    //     $recommandations = $recommandations = array(
    //         array('uuid' => '8b921900-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 5','description' => 'Former au moins une personne supplémentaire à l\'utilisation des machines.','importance' => '3','position' => '3','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:06','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b92190a-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 9','description' => 'Mettre en place une charte utilisateur précisant les limites d\'utilisation du système d\'information et les bonnes pratiques minimales (email, mots de passe, broyage, ...)','importance' => '2','position' => '9','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:06','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b92191d-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 1','description' => 'Définir les règles d\'utilisation de et sortie du matériel, pour tout le personnel.','importance' => '1','position' => '11','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:07','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b924017-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 88','description' => 'Mettre en place un contrôle d\'accès rigoureux incluant le besoin d\'en connaître','importance' => '3','position' => '6','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:08','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b924022-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 34','description' => 'Mettre un accès par badge et une rédiger une note interdisant de caler la porte.','importance' => '3','position' => '1','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:13','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b92402a-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 2','description' => 'Accompagner les personnes externes pour toute intervention dans le Datacenter.','importance' => '2','position' => '7','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:13','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b924035-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 4','description' => 'Définir une procédure de maintenance qui prévient du vieillissement du matériel.','importance' => '3','position' => '2','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:15','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b92403a-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 7','description' => 'Tester périodiquement la procédure de restauration à partir d\'un échantillon représentatif de données.','importance' => '3','position' => '4','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:16','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b924041-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 8','description' => 'Stocker les cassettes de backup dans une armoire verrouillée où seul l\'informaticien à accès.','importance' => '3','position' => '5','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:16','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b926729-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'Rec 6','description' => 'Revoir périodiquement les autorisations d\'accès.','importance' => '2','position' => '8','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:16','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28'),
    //         array('uuid' => '8b926737-7301-11e9-b475-0800200c9a66','anr_id' => NULL,'code' => 'REC RISQUE OP','description' => 'Faire n\'importe quoi','importance' => '2','position' => '10','comment' => NULL,'responsable' => NULL,'duedate' => NULL,'counter_treated' => '0','original_code' => NULL,'token_import' => NULL,'creator' => 'Admin Admin','created_at' => '2019-04-25 10:12:21','updater' => 'Admin Admin','updated_at' => '2019-04-25 10:12:28')
    //       );
    //     $this->insert("recommandations", $recommandations);

    //     //remove the id
    //     $table->removeColumn('id')
    //         ->save();
    //     $this->execute("ALTER TABLE recommandations ADD PRIMARY KEY uuid (uuid)");
    // }
}
