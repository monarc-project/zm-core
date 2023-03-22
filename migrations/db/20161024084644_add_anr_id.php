<?php

use Phinx\Migration\AbstractMigration;

class AddAnrId extends AbstractMigration
{
    public function up()
    {
        //objects categories
        $table = $this->table('objects_categories');
        $table->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'id'))
            ->addIndex(array('anr_id'))
            ->update();

        $table = $this->table('objects_categories');
        $table->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }

    public function down()
    {

        //objects categories
        $table = $this->table('objects_categories');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();
    }
}
