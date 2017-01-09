<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class ForeignKeysRolfRiskCategories extends AbstractMigration
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
        $table = $this->table('rolf_risks_categories');
        $table
            ->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('rolf_category_id', 'rolf_categories', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        $table = $this->table('rolf_risks_tags');
        if ($table->hasForeignKey('rolf_risk_id')) {
            $table->addForeignKey('rolf_risk_id', 'rolf_risks', 'id', array('delete' => 'CASCADE', 'update' => 'RESTRICT'));
        }
        if ($table->hasForeignKey('rolf_tag_id')) {
            $table->addForeignKey('rolf_tag_id', 'rolf_tags', 'id', array('delete' => 'CASCADE', 'update' => 'RESTRICT'));
        }
        $table->update();

    }

    public function down()
    {
        $table = $this->table('rolf_risks_categories');
        if ($table->hasForeignKey('rolf_risk_id')) {
            $table->dropForeignKey('rolf_risk_id');
        }
        if ($table->hasForeignKey('rolf_category_id')) {
            $table->dropForeignKey('rolf_category_id');
        }
        $table->update();

        $table = $this->table('rolf_risks_tags');
        if ($table->hasForeignKey('rolf_risk_id')) {
            $table->dropForeignKey('rolf_risk_id');
        }
        if ($table->hasForeignKey('rolf_tag_id')) {
            $table->dropForeignKey('rolf_tag_id');
        }
        $table->update();
    }
}
