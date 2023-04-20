<?php

use Phinx\Migration\AbstractMigration;

class ObjectAddModelId extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('objects');
        $table
            ->addColumn('model_id', 'integer', array('null' => true, 'signed' => false))
            ->addIndex(array('model_id'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->update();
    }
}
