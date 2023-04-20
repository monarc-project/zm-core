<?php

use Phinx\Migration\AbstractMigration;

class InstancesRisksOp extends AbstractMigration
{
    public function change()
    {

        $table = $this->table('instances_risks_op');
        $table
            ->addForeignKey('object_id', 'objects', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
