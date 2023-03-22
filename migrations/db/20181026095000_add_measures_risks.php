<?php

use Phinx\Migration\AbstractMigration;

class AddMeasuresRisks extends AbstractMigration
{
    public function change()
    {
      // Migration for table measures_amvs
      $table = $this->table('measures_amvs');
      $table
          //->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('amv_id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('measure_id', 'integer', array('null' => true, 'signed' => false))
          ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('created_at', 'datetime', array('null' => true))
          ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
          ->addColumn('updated_at', 'datetime', array('null' => true))
          //->addIndex(array('anr_id'))
          ->addIndex(array('amv_id'))
          ->addIndex(array('measure_id'))
          ->create();
      $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();
      $table->addForeignKey('amv_id', 'amvs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('measure_id', 'measures', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

      $this->query('INSERT INTO measures_amvs ( measure_id,amv_id) SELECT  amvs.measure1_id, amvs.id FROM amvs where amvs.measure1_id is not null;');
      $this->query('INSERT INTO measures_amvs ( measure_id,amv_id) SELECT  amvs.measure2_id, amvs.id FROM amvs where amvs.measure2_id is not null;');
      $this->query('INSERT INTO measures_amvs ( measure_id,amv_id) SELECT  amvs.measure3_id, amvs.id FROM amvs where amvs.measure3_id is not null;');

      $table = $this->table('amvs');
      $table->dropForeignKey('measure1_id')
            ->dropForeignKey('measure2_id')
            ->dropForeignKey('measure3_id')
            ->save();
      $table->removeColumn('measure1_id')
            ->removeColumn('measure2_id')
            ->removeColumn('measure3_id')
            ->save();
      //simplify the management of the SOA categories
      $table = $this->table('soacategory');
      $table->removeColumn('code')->save();
    }
}
