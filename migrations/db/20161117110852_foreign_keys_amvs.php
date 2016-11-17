<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ForeignKeysAmvs extends AbstractMigration
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
        $table = $this->table('amvs');

        $exists = $table->hasForeignKey('measure1_id');
        if ($exists) {
            $table->dropForeignKey('measure1_id');
        }
        $table->update();

        $exists = $table->hasForeignKey('measure2_id');
        if ($exists) {
            $table->dropForeignKey('measure2_id');
        }
        $table->update();

        $exists = $table->hasForeignKey('measure3_id');
        if ($exists) {
            $table->dropForeignKey('measure3_id');
        }
        $table->update();


        $table
            ->addForeignKey('measure1_id', 'measures', 'id', array('delete' => 'SET NULL','update' => 'RESTRICT'))
            ->addForeignKey('measure2_id', 'measures', 'id', array('delete' => 'SET NULL','update' => 'RESTRICT'))
            ->addForeignKey('measure3_id', 'measures', 'id', array('delete' => 'SET NULL','update' => 'RESTRICT'))
            ->update();
    }
}
