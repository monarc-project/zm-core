<?php

use Phinx\Migration\AbstractMigration;

class CleanSoaCommonEntities extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('soa');
        $table->removeColumn('control1')
              ->removeColumn('control2')
              ->removeColumn('control3')
              ->removeColumn('control4')
              ->removeColumn('requirement')
              ->removeColumn('justification')
              ->removeColumn('evidences')
              ->removeColumn('actions')
              ->removeColumn('compliance')
              ->save();
    }
}
