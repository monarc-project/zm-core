<?php

use Phinx\Migration\AbstractMigration;

class InstanceRisksOpForeignKeys extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('instances_risks_op');
        $exists = $table->hasForeignKey('rolf_risk_id');
        if ($exists) {
            $table->dropForeignKey('rolf_risk_id');
        }
        $table->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->update();
    }

}
