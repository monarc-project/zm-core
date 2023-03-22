<?php

use Phinx\Migration\AbstractMigration;

class UpdateTrendFieldDefault extends AbstractMigration
{
    public function change()
    {
        $this->table('threats')
            ->changeColumn('trend', 'integer', array('default' => 1))
            ->save();
        $this->execute('UPDATE threats SET trend = 1');
    }
}
