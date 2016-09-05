<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class DeleteObjectsRisks extends AbstractMigration
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
        $this->dropTable('objects_risks');

        $table = $this->table('anrs_objects');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('object_id', 'integer', array('null' => true, 'signed' => false))
            ->addIndex(array('anr_id'))
            ->addIndex(array('object_id'))
            ->create();

        $table = $this->table('anrs_objects');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        $table = $this->table('objects');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();

        $table = $this->table('objects');
        $table->removeColumn('type')->update();

        $table = $this->table('objects');
        $exists = $table->hasForeignKey('source_bdc_object_id');
        if ($exists) {
            $table->dropForeignKey('source_bdc_object_id');
        }
        $table->removeColumn('source_bdc_object_id')->update();
    }

    public function down()
    {
        $table = $this->table('objects_risks');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('object_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('specific', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('amv_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('asset_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('threat_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('vulnerability_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('mh', 'integer', array('default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('threat_rate', 'integer', array('default' => '-1', 'limit' => 11))
            ->addColumn('vulnerability_rate', 'integer', array('default' => '-1', 'limit' => 11))
            ->addColumn('kind_of_measure', 'integer', array('null' => true, 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('reduction_amount', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('risk_c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('risk_i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('risk_d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('object_id'))
            ->addIndex(array('amv_id'))
            ->addIndex(array('asset_id'))
            ->addIndex(array('threat_id'))
            ->addIndex(array('vulnerability_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

        $table = $this->table('objects_risks');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('amv_id', 'amvs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('threat_id', 'threats', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('vulnerability_id', 'vulnerabilities', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        $this->dropTable('objects_risks');

        $table = $this->table('objects');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('type', 'char', array('null' => true, 'default' => 'anr', 'limit' => 3))
            ->addColumn('source_bdc_object_id', 'integer', array('null' => true, 'signed' => false))
            ->addIndex(array('anr_id'))
            ->addIndex(array('source_bdc_object_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

        $table = $this->table('objects');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('source_bdc_object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
