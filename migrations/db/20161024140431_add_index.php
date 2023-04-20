<?php

use Phinx\Migration\AbstractMigration;

class AddIndex extends AbstractMigration
{
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
