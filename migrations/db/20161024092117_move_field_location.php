<?php

use Phinx\Migration\AbstractMigration;

class MoveFieldLocation extends AbstractMigration
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
        $table = $this->table('instances');
        $table->changeColumn('parent_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'root_id'))
            ->update();

        $table = $this->table('objects');
        $table->changeColumn('model_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'rolf_tag_id'))
            ->update();
    }

}
