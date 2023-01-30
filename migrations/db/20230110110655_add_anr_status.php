<?php

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class AddAnrStatus extends AbstractMigration
{
    public function change()
    {
        $this->table('anrs')
            ->addColumn(
                'status',
                'integer',
                ['null' => false, 'signed' => false, 'default' => 1, 'limit' => MysqlAdapter::INT_TINY]
            )
            ->update();
    }
}
