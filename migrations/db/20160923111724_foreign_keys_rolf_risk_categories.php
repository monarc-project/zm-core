<?php

use Phinx\Migration\AbstractMigration;

class ForeignKeysRolfRiskCategories extends AbstractMigration
{
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
