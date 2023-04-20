<?php

use Phinx\Migration\AbstractMigration;

class DeleteRisksCategories extends AbstractMigration
{
    public function up()
    {
        $this->table('rolf_risks_categories')->drop()->save();
    }

    public function down()
    {
        $table = $this->table('rolf_risks_categories');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_risk_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('rolf_category_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->addIndex(array('rolf_risk_id'))
            ->addIndex(array('rolf_category_id'))
            ->create();
        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();
    }
}
