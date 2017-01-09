<?php

use Phinx\Migration\AbstractMigration;

class FixObjectsClone extends AbstractMigration
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
        $this->execute('UPDATE objects set name1 = "" WHERE name1 = " (copy)"');
        $this->execute('UPDATE objects set name2 = "" WHERE name2 = " (copy)"');
        $this->execute('UPDATE objects set name3 = "" WHERE name3 = " (copy)"');
        $this->execute('UPDATE objects set name4 = "" WHERE name4 = " (copy)"');
    }
}
