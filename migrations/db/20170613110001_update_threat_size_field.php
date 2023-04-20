<?php

use Phinx\Migration\AbstractMigration;

class UpdateThreatSizeField extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('threats');
        $table->changeColumn('description1', 'string', array('null' => true, 'limit' => 1024))
					->changeColumn('description2', 'string', array('null' => true, 'limit' => 1024))
					->changeColumn('description3', 'string', array('null' => true, 'limit' => 1024))
					->changeColumn('description4', 'string', array('null' => true, 'limit' => 1024))
					->update();

    }
}
