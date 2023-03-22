<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class DeleteChIhDhConsequences extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('instances_consequences');
        $table->removeColumn('ch')
            ->removeColumn('ih')
            ->removeColumn('dh')
            ->update();
    }

    public function down()
    {
        $table = $this->table('instances_consequences');
        $table->addColumn('ch', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('ih', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('dh', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))
            ->update();
    }
}
