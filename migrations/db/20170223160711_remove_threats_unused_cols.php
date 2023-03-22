<?php

use Phinx\Migration\AbstractMigration;

class RemoveThreatsUnusedCols extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('threats');
        $table->removeColumn('is_accidental')
            ->removeColumn('is_deliberate')
            ->removeColumn('desc_accidental1')
            ->removeColumn('desc_accidental2')
            ->removeColumn('desc_accidental3')
            ->removeColumn('desc_accidental4')
            ->removeColumn('ex_accidental1')
            ->removeColumn('ex_accidental2')
            ->removeColumn('ex_accidental3')
            ->removeColumn('ex_accidental4')
            ->removeColumn('desc_deliberate1')
            ->removeColumn('desc_deliberate2')
            ->removeColumn('desc_deliberate3')
            ->removeColumn('desc_deliberate4')
            ->removeColumn('ex_deliberate1')
            ->removeColumn('ex_deliberate2')
            ->removeColumn('ex_deliberate3')
            ->removeColumn('ex_deliberate4')
            ->removeColumn('type_consequences1')
            ->removeColumn('type_consequences2')
            ->removeColumn('type_consequences3')
            ->removeColumn('type_consequences4')
            ->save();
    }
}
