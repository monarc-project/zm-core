<?php

use Phinx\Migration\AbstractMigration;

class UpdateThreatSizeField extends AbstractMigration
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
        $table = $this->table('threats');
        $table->changeColumn('description1', 'string', array('null' => true, 'limit' => 1024))
					->changeColumn('description2', 'string', array('null' => true, 'limit' => 1024))
					->changeColumn('description3', 'string', array('null' => true, 'limit' => 1024))
					->changeColumn('description4', 'string', array('null' => true, 'limit' => 1024))
					->update();

    }
}
