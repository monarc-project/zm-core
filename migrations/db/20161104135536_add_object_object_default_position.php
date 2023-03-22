<?php

use Phinx\Migration\AbstractMigration;

class AddObjectObjectDefaultPosition extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('objects_objects');
        $table->changeColumn('position', 'integer', array('null' => false, 'default' => '1', 'signed' => false))
            ->save();
    }
}
