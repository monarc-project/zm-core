<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateDocModelsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('doc_models');
        $table
            ->addColumn('category', 'integer', array('default' => 0, 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('description', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('path', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('content', 'blob', array('null' => true, 'limit' => MysqlAdapter::BLOB_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();
    }
}
