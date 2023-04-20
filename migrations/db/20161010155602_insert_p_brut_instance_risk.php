<?php

use Phinx\Migration\AbstractMigration;

class InsertPBrutInstanceRisk extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('instances_risks_op');
        $table
            ->addColumn('brut_p', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->update();
    }

    public function down()
    {
        $table = $this->table('instances_risks_op');
        if($table->hasColumn('brut_p')){
            $table->removeColumn('brut_p');
        }
        $table->update();
    }
}
