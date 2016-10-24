<?php

use Phinx\Migration\AbstractMigration;

class AddAnrId extends AbstractMigration
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
    public function up()
    {
        //rolf risks
        $table = $this->table('rolf_risks');
        $table->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'id'))
            ->addIndex(array('anr_id'))
            ->update();

        $table = $this->table('rolf_risks');
        $table->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        //rolf tags
        $table = $this->table('rolf_tags');
        $table->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'id'))
            ->addIndex(array('anr_id'))
            ->update();

        $table = $this->table('rolf_tags');
        $table->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        //rolf categories
        $table = $this->table('rolf_categories');
        $table->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'id'))
            ->addIndex(array('anr_id'))
            ->update();

        $table = $this->table('rolf_categories');
        $table->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        //objects
        $table = $this->table('objects');
        $table->changeColumn('anr_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'id'))
            ->update();

        //objects categories
        $table = $this->table('objects_categories');
        $table->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false, 'after' => 'id'))
            ->addIndex(array('anr_id'))
            ->update();

        $table = $this->table('objects_categories');
        $table->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

    }

    public function down()
    {
        //rolf risks
        $table = $this->table('rolf_risks');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();

        //rolf tags
        $table = $this->table('rolf_tags');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();

        //rolf categories
        $table = $this->table('rolf_categories');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();

        //objects
        $table = $this->table('objects');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();

        //objects categories
        $table = $this->table('objects_categories');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();
    }
}
