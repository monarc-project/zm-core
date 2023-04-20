<?php

use Phinx\Migration\AbstractMigration;

class RemoveAnrIdRolfTables extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('rolf_risks_tags');
        if ($table->hasForeignKey('anr_id')) {
            $table->dropForeignKey('anr_id')->save();
        }
        $table->removeColumn('anr_id')->update();
    }
}
