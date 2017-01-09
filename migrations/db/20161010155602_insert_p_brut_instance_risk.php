<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class InsertPBrutInstanceRisk extends AbstractMigration
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
            ->addColumn('brut_p', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->update();
    }

    public function down()
    {
        $table = $this->table('instances_risks_op');
        if($table->hasColumn('brut_p')){
            $table->removeColumn('brut_p');
        }
        $table->update();
    }
}
