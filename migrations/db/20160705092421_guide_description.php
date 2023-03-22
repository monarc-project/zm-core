<?php

use Phinx\Migration\AbstractMigration;

class GuideDescription extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('guides');
        $table->changeColumn('description1', 'text', array('null' => true))->update();
        $table->changeColumn('description2', 'text', array('null' => true))->update();
        $table->changeColumn('description3', 'text', array('null' => true))->update();
        $table->changeColumn('description4', 'text', array('null' => true))->update();

    }
}
