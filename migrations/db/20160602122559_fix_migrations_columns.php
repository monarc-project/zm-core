<?php

use Phinx\Migration\AbstractMigration;

class FixMigrationsColumns extends AbstractMigration
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
        $table = $this->table('threats');
        $exists = $table->hasForeignKey('threat_theme_id');
        if ($exists) {
            $table->dropForeignKey('threat_theme_id');
        }
        $table->renameColumn('threat_theme_id','theme_id');
        $table->addForeignKey('theme_id', 'themes', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))->update();
    }
}
