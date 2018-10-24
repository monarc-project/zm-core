<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class HarmonizeDb extends AbstractMigration
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
        $table = $this->table('measures');
        $table
            ->renameColumn('description1','label1')
            ->renameColumn('description2','label2')
            ->renameColumn('description3','label3')
            ->renameColumn('description4','label4')
            ->changeColumn('soacategory_id', 'integer', array('null' => true, 'signed' => false,'after' => 'anr_id'))
            ->update();

        $table = $this->table('threats');
        $table
            ->renameColumn('d','a')
            ->update();

        $table = $this->table('soacategory');
        $table
            ->renameColumn('reference','code')
            ->update();
    }
}
