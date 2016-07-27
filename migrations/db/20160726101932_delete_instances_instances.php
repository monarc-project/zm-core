<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class DeleteInstancesInstances extends AbstractMigration
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

        //instances
        $this->table('instances')
            ->addColumn('position', 'integer', array('null' => true, 'default' => '0', 'limit' => 11))
            ->addColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('ch', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('ih', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('dh', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->save();

        //instances consequences
        $table = $this->table('instances_consequences');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('instance_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('object_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('scale_impact_type_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addColumn('is_hidden', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('locally_touched', 'integer', array('null' => true, 'default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('ch', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('ih', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('dh', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('instance_id'))
            ->addIndex(array('object_id'))
            ->addIndex(array('scale_impact_type_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

        $table = $this->table('instances_consequences');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('instance_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('scale_impact_type_id', 'scales_impact_types', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        $this->table('instances_instances_consequences')
            ->dropForeignKey('anr_id')
            ->dropForeignKey('instance_id')
            ->dropForeignKey('instance_instance_id')
            ->dropForeignKey('object_id')
            ->dropForeignKey('scale_impact_type_id')
            ->update();

        $this->dropTable('instances_instances_consequences');

        //instances risks
        $this->table('instances_risks')
            ->dropForeignKey('instance_instance_id')
            ->removeColumn('instance_instance_id')
            ->save();

        $this->table('instances_risks')
            ->addColumn('instance_id', 'integer', array('null' => true, 'default' => '0', 'signed' => false))
            ->addForeignKey('instance_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->save();

        //instances instances
        $this->table('instances_instances')
            ->dropForeignKey('anr_id')
            ->dropForeignKey('father_id')
            ->dropForeignKey('child_id')
            ->update();

        $this->dropTable('instances_instances');

        //add father_id in instances
        $table = $this->table('instances');
        $table
            ->addColumn('parent_id', 'integer', array('null' => true, 'signed' => false))
            ->save();

        $table = $this->table('instances');
        $table
            ->addForeignKey('parent_id', 'instances', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
