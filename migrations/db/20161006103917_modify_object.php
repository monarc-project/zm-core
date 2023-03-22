<?php

use Phinx\Migration\AbstractMigration;

class ModifyObject extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('objects');

        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->save();

        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
