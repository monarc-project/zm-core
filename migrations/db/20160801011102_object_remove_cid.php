<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ObjectRemoveCid extends AbstractMigration
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
        $table = $this->table('objects');
        if($table->hasColumn('c')){
            $table->removeColumn('c');
        }
        if($table->hasColumn('i')){
            $table->removeColumn('i');
        }
        if($table->hasColumn('d')){
            $table->removeColumn('d');
        }
        $table->update();
    }
    public function down()
    {
        $table = $this->table('objects');
        $table
            ->addColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->update();
    }
}
