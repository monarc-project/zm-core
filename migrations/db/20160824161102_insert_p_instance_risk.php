<?php

use Phinx\Migration\AbstractMigration;

class InsertPInstanceRisk extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('instances_risks_op');
        $table
            ->addColumn('net_p', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('targeted_p', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->update();
    }

    public function down()
    {
        $table = $this->table('instances_risks_op');
        if($table->hasColumn('net_p')){
            $table->removeColumn('net_p');
        }
        if($table->hasColumn('targeted_p')){
            $table->removeColumn('targeted_p');
        }
        $table->update();
    }
}
