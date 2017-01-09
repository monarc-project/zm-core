<?php

use Phinx\Migration\AbstractMigration;

class ModifyScale extends AbstractMigration
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
        $table = $this->table('scales_impact_types');
        $table->renameColumn('label','label1');
        $table
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->save();
    }
}
