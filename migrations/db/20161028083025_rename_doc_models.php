<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class RenameDocModels extends AbstractMigration
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
    public function up()
    {
        $this->dropTable('deliveries');
        $this->dropTable('deliveries_models');

        $table = $this->table('doc_models');
        $table->rename('deliveries_models');
        $table->renameColumn('description', 'description1');
        $table->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->update();
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
    public function down()
    {
        $table = $this->table('deliveries_models');
        $table->rename('doc_models');
        $table->dropForeignKey('anr_id');
        $table->renameColumn('description1', 'description');
        $table->removeColumn('description2')
            ->removeColumn('description3')
            ->removeColumn('description4')
            ->removeColumn('anr_id')
            ->update();

        // Migration for table deliveries
        $table = $this->table('deliveries');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('typedoc', 'integer', array('default' => '0', 'limit' => 11))
            ->addColumn('name', 'text', array())
            ->addColumn('version', 'float', array('default' => '0.00','precision'=>11,'scale'=>2))
            ->addColumn('status', 'integer', array('default' => '0', 'limit' => 11))
            ->addColumn('classification', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('resp_customer', 'string', array('default' => '0', 'limit' => 255))
            ->addColumn('resp_smile', 'string', array('default' => '0', 'limit' => 255))
            ->addColumn('summary_eval_risk', 'text', array('limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        // Migration for table deliveries_models
        $table = $this->table('deliveries_models');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('typedoc', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('name1', 'string', array('limit' => 255))
            ->addColumn('name2', 'string', array('limit' => 255))
            ->addColumn('name3', 'string', array('limit' => 255))
            ->addColumn('name4', 'string', array('limit' => 255))
            ->addColumn('content', 'blob', array('default' => '', 'limit'=>MysqlAdapter::BLOB_LONG))
            ->addColumn('description1', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('description2', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('description3', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('description4', 'string', array('default' => '', 'limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('typedoc'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
