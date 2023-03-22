<?php

use Phinx\Migration\AbstractMigration;

class AddMeasuresMeasures extends AbstractMigration
{
    public function change()
    {
      // Migration for table measures_measures to link measures set primary key composed of id of measures to avoid duplicata
      $table = $this->table('measures_measures');
      $table
          ->addColumn('father_id', 'integer', array('null' => false, 'signed' => false))
          ->addColumn('child_id', 'integer', array('null' => false, 'signed' => false))
          ->addForeignKey('father_id', 'measures', 'id', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
          ->addForeignKey('child_id', 'measures', 'id', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
          ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('created_at', 'datetime', array('null' => true))
          ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('updated_at', 'datetime', array('null' => true))
          ->addIndex(array('father_id'))
          ->addIndex(array('child_id'))
          ->create();
      $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();
    }
}
