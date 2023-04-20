<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class DeleteSnapshot extends AbstractMigration
{
    public function up()
    {
        //table anrs
        $table = $this->table('anrs');
        $exists = $table->hasForeignKey('snapshot_id');
        if ($exists) {
            $table->dropForeignKey('snapshot_id');
        }
        $exists = $table->hasForeignKey('snapshot_ref_id');
        if ($exists) {
            $table->dropForeignKey('snapshot_ref_id');
        }
        $table->removeColumn('snapshot_id')->update();
        $table->removeColumn('snapshot_ref_id')->update();

        //table snapshots
        $this->table('snapshots')->drop()->save();
    }

    public function down()
    {
        //table anrs
        $table = $this->table('anrs');
        $table
            ->addColumn('snapshot_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('snapshot_ref_id', 'integer', array('null' => true, 'signed' => false))
            ->addIndex(array('snapshot_id'))
            ->addIndex(array('snapshot_ref_id'))
            ->update();

        $table
            ->addForeignKey('snapshot_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('snapshot_ref_id', 'snapshots', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        //table snapshots
        $table = $this->table('snapshots');

        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('anr_reference_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('comment', 'text', array('null' => true, 'limit' => MysqlAdapter::TEXT_LONG))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('anr_reference_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('anr_reference_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
