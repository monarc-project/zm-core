<?php

use Phinx\Migration\AbstractMigration;

class AddObjectObjectPosition extends AbstractMigration
{
    public function change()
    {
        $this->table('objects_objects')
            ->addColumn('position', 'integer', array('null' => false, 'signed' => false))
            ->save();
    }
}
