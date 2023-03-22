<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CidConsequencesDefaultValue extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('instances_consequences');
        $table->changeColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => MysqlAdapter::INT_TINY))
            ->changeColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => MysqlAdapter::INT_TINY))
            ->changeColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => MysqlAdapter::INT_TINY))
            ->update();
    }
}
