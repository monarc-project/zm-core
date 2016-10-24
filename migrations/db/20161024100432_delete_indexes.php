<?php

use Phinx\Migration\AbstractMigration;

class DeleteIndexes extends AbstractMigration
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
        $this->query('ALTER TABLE monarc_cli.rolf_categories DROP FOREIGN KEY rolf_categories_ibfk_1');
        $this->query('ALTER TABLE monarc_cli.rolf_risks DROP FOREIGN KEY rolf_risks_ibfk_1');
        $this->query('ALTER TABLE monarc_cli.rolf_tags DROP FOREIGN KEY rolf_tags_ibfk_1');

        //rolf categories
        $table = $this->table('rolf_categories');
        $exists = $table->hasIndex('anr_id_3');
        if ($exists) {
            $table->removeIndexByName('anr_id_3');
            $table->removeIndex(array('anr_id_3'));
        }

        $table = $this->table('rolf_categories');
        $exists = $table->hasIndex('anr_id');
        if ($exists) {
            $table->removeIndexByName('anr_id');
            $table->removeIndex(array('anr_id'));
        }

        //rolf risks
        $table = $this->table('rolf_risks');
        $exists = $table->hasIndex('anr_id');
        if ($exists) {
            $table->removeIndexByName('anr_id');
            $table->removeIndex(array('anr_id'));
        }

        $table = $this->table('rolf_risks');
        $exists = $table->hasIndex('anr_id_3');
        if ($exists) {
            $table->removeIndexByName('anr_id_3');
            $table->removeIndex(array('anr_id_3'));
        }

        //rolf tags
        $table = $this->table('rolf_tags');
        $exists = $table->hasIndex('anr_id');
        if ($exists) {
            $table->removeIndexByName('anr_id');
            $table->removeIndex(array('anr_id'));
        }

        $table = $this->table('rolf_tags');
        $exists = $table->hasIndex('anr_id_2');
        if ($exists) {
            $table->removeIndexByName('anr_id_2');
            $table->removeIndex(array('anr_id_2'));
        }

        $table = $this->table('rolf_tags');
        $exists = $table->hasIndex('anr_id_3');
        if ($exists) {
            $table->removeIndexByName('anr_id_3');
            $table->removeIndex(array('anr_id_3'));
        }
    }

}
