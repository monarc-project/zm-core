<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddTranslatedDeliveryModels extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('deliveries_models');

        $table->renameColumn('path', 'path1');
        $table->renameColumn('content', 'content1');

        $table->addColumn('path2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('content2', 'blob', array('null' => true, 'limit' => MysqlAdapter::BLOB_LONG))
            ->addColumn('path3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('content3', 'blob', array('null' => true, 'limit' => MysqlAdapter::BLOB_LONG))
            ->addColumn('path4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('content4', 'blob', array('null' => true, 'limit' => MysqlAdapter::BLOB_LONG));

        $table->update();
    }
}
