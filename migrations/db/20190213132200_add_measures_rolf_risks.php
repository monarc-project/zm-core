<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;
use Ramsey\Uuid\Uuid;

class AddMeasuresRolfRisks extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
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
