<?php

use Phinx\Migration\AbstractMigration;

class DeleteIndexes extends AbstractMigration
{
    public function change()
    {
        //rolf risks
        $table = $this->table('rolf_risks');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id')->save();
        }

        $table = $this->table('rolf_risks');
        $exists = $table->hasIndex('anr_id');
        if ($exists) {
            $table->removeIndexByName('anr_id')
                ->removeIndex(array('anr_id'))
                ->save();
        }

        $table = $this->table('rolf_risks');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        //rolf tags
        $table = $this->table('rolf_tags');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id')->save();
        }

        $table = $this->table('rolf_tags');
        $table
            ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }

}
