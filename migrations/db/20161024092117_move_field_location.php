<?php

use Phinx\Migration\AbstractMigration;

class MoveFieldLocation extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('instances');
        $table->changeColumn('parent_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'root_id'))
            ->update();

        $table = $this->table('objects');
        $table->changeColumn('model_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'rolf_tag_id'))
            ->update();
    }

}
