<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ObjectRemoveDescriptions extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('objects');
        $table->removeColumn('description1')
            ->removeColumn('description2')
            ->removeColumn('description3')
            ->removeColumn('description4')
            ->update();
    }
    public function down()
    {
        $table = $this->table('objects');
        $table
            ->addColumn('description1', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description2', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description3', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('description4', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->update();
    }
}
