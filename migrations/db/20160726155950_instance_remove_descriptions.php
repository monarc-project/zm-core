<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class InstanceRemoveDescriptions extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('instances');
        $table->removeColumn('description1')
            ->removeColumn('description2')
            ->removeColumn('description3')
            ->removeColumn('description4')
            ->update();
    }
    public function down()
    {
        $table = $this->table('instances');
        $table
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->update();
    }
}
