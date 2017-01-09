<?php

use Phinx\Migration\AbstractMigration;

class AddIndex extends AbstractMigration
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
        $tables = ['rolf_categories', 'rolf_tags', 'rolf_risks'];

        foreach($tables as $tableName) {

            $table = $this->table($tableName);
            $table
                ->addIndex(array('anr_id', 'code'), array('unique' => true))
                ->addIndex(array('anr_id'))
                ->update();

            $table = $this->table($tableName);
            $table
                ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE', 'update' => 'RESTRICT'))
                ->update();

        }
    }

}
