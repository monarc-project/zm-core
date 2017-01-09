<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class InsertPInstanceRisk extends AbstractMigration
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
        $table = $this->table('instances_risks_op');
        $table
            ->addColumn('net_p', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('targeted_p', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->update();
    }

    public function down()
    {
        $table = $this->table('instances_risks_op');
        if($table->hasColumn('net_p')){
            $table->removeColumn('net_p');
        }
        if($table->hasColumn('targeted_p')){
            $table->removeColumn('targeted_p');
        }
        $table->update();
    }
}
