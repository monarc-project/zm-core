<?php

use Phinx\Migration\AbstractMigration;

class HarmonizeDb extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('measures');
        $table
            ->renameColumn('description1','label1')
            ->renameColumn('description2','label2')
            ->renameColumn('description3','label3')
            ->renameColumn('description4','label4')
            ->changeColumn('soacategory_id', 'integer', array('null' => true, 'signed' => false,'after' => 'anr_id'))
            ->update();

        $table = $this->table('threats');
        $table
            ->renameColumn('d','a')
            ->update();

        $table = $this->table('soacategory');
        $table
            ->renameColumn('reference','code')
            ->update();
    }
}
