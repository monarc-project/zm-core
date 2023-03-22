<?php

use Phinx\Migration\AbstractMigration;

class ObjectRemoveCid extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('objects');
        if($table->hasColumn('c')){
            $table->removeColumn('c');
        }
        if($table->hasColumn('i')){
            $table->removeColumn('i');
        }
        if($table->hasColumn('d')){
            $table->removeColumn('d');
        }
        $table->update();
    }
    public function down()
    {
        $table = $this->table('objects');
        $table
            ->addColumn('c', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('i', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->addColumn('d', 'integer', array('null' => true, 'default' => '-1', 'limit' => 11))
            ->update();
    }
}
