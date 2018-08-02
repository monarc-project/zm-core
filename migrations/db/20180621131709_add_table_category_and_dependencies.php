<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;


class AddTableCategoryAndDependencies extends AbstractMigration
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

      // Migration for table category
      $table = $this->table('category');
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
      ->addColumn('category_id', 'integer',  array('null' => true, 'default' => '15',  'signed' => false))
      ->save();






    }
}
