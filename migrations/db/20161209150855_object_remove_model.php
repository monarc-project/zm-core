<?php

use Phinx\Migration\AbstractMigration;

class ObjectRemoveModel extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('objects');
        $exists = $table->hasForeignKey('model_id');
        if ($exists) {
            $table->dropForeignKey('model_id');
        }
        $table
            ->removeColumn('model_id')
            ->update();
    }

    public function down(){
        $table = $this->table('objects');
        $table
            ->addColumn('model_id', 'integer', array('null' => true, 'signed' => false))
            ->addIndex(array('model_id'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'SET_NULL','update' => 'RESTRICT'))
            ->update();
    }
}
