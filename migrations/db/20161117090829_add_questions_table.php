<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class AddQuestionsTable extends AbstractMigration
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
        $table = $this->table('questions');
        $table
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('position', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('type', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('multichoice', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))

            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))

            ->create();

        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

        $table = $this->table('questions_choices');
        $table
            ->addColumn('label1', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label2', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label3', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('label4', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('position', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('type', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))
            ->addColumn('multichoice', 'integer', array('default' => '0', 'limit' => MysqlAdapter::INT_TINY))

            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))

            ->create();

        $table->changeColumn('id', 'integer',array('identity'=>true,'signed'=>false))->update();

        $table->addColumn('question_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'id'))
            ->addIndex(array('question_id'))
            ->update();
    }
}
