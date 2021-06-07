<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddOwnerContextRisks extends AbstractMigration
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
        // Migration for table instances
        $table = $this->table('instances');
        $table
            ->addColumn('owner', 'string', array('null' => true, 'limit' => 255, 'after' => 'root_id'))
            ->update();

        // Migration for table instances_risks
        $table = $this->table('instances_risks');
        $table
            ->addColumn('owner', 'string', array('null' => true, 'limit' => 255, 'after' => 'instance_id'))
            ->addColumn('context', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_REGULAR, 'after' => 'owner'))
            ->update();

        // Migration for table instances_risks_op
        $table = $this->table('instances_risks_op');
        $table
            ->addColumn('owner', 'string', array('null' => true, 'limit' => 255, 'after' => 'instance_id'))
            ->addColumn('context', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_REGULAR, 'after' => 'owner'))
            ->update();
    }
}
