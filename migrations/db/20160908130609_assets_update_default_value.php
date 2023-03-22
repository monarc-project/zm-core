<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AssetsUpdateDefaultValue extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('assets');
        $table->changeColumn('type', 'integer', array('null' => true, 'default' => '1', 'limit' => MysqlAdapter::INT_TINY))->update();
    }
}
