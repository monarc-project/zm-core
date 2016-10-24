<?php

use Phinx\Migration\AbstractMigration;

class ChangeIndex extends AbstractMigration
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
            $foreignKeys = [$tableName . '_ibfk_1', 'anr_id_3', 'anr_id_2', 'anr_id'];
            foreach ($foreignKeys as $key) {
                $table = $this->table($tableName);
                if ($table->hasForeignKey($key)) {
                    $table->dropForeignKey($key);
                }
                $table->update();
            }

            $indexes = ['anr_id_3', 'anr_id_2', 'anr_id'];
            foreach ($indexes as $index) {
                $table = $this->table($tableName);
                if ($table->hasIndex($index)) {
                    $table->removeIndex($index);
                }
                $table->update();
            }
            
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
