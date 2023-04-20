<?php

use Phinx\Migration\AbstractMigration;

class ForeignKeysAmvs extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('amvs');

        $exists = $table->hasForeignKey('measure1_id');
        if ($exists) {
            $table->dropForeignKey('measure1_id');
        }
        $table->update();

        $exists = $table->hasForeignKey('measure2_id');
        if ($exists) {
            $table->dropForeignKey('measure2_id');
        }
        $table->update();

        $exists = $table->hasForeignKey('measure3_id');
        if ($exists) {
            $table->dropForeignKey('measure3_id');
        }
        $table->update();

        $table
            ->addForeignKey('measure1_id', 'measures', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->addForeignKey('measure2_id', 'measures', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->addForeignKey('measure3_id', 'measures', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->update();
    }
}
