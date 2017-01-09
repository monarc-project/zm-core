<?php

use Phinx\Migration\AbstractMigration;

class GuideDescription extends AbstractMigration
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
        $table = $this->table('guides');
        $table->changeColumn('description1', 'text', array('null' => true))->update();
        $table->changeColumn('description2', 'text', array('null' => true))->update();
        $table->changeColumn('description3', 'text', array('null' => true))->update();
        $table->changeColumn('description4', 'text', array('null' => true))->update();

    }
}
