<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class DeleteInterviews extends AbstractMigration
{
    public function up()
    {
        $this->table('interviews')->drop()->save();
    }

    public function down()
    {
        // Migration for table interviews
        $table = $this->table('interviews');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('date', 'datetime', array('null' => true))
            ->addColumn('service', 'text', array('null' => true))
            ->addColumn('content', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

        $table = $this->table('interviews');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
