<?php

use Phinx\Migration\AbstractMigration;

class ModifyScale extends AbstractMigration
{
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
