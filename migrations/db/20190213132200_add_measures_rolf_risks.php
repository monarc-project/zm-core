<?php

use Phinx\Migration\AbstractMigration;

class AddMeasuresRolfRisks extends AbstractMigration
{
    public function change()
    {
      $table = $this->table('measures_rolf_risks');
      $table
          ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('rolf_risk_id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('measure_id', 'uuid')
          ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('created_at', 'datetime', array('null' => true))
          ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('updated_at', 'datetime', array('null' => true))
          ->create();
      $table
          ->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
          ->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
          ->addForeignKey('measure_id', 'measures', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'RESTRICT'])
          ->update();
    }
}
