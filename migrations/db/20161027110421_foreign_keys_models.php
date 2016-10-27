<?php

use Phinx\Migration\AbstractMigration;

class ForeignKeysModels extends AbstractMigration
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
        $this->query('ALTER TABLE monarc_common.assets_models DROP FOREIGN KEY assets_models_ibfk_1');
        $this->query('ALTER TABLE monarc_common.assets_models DROP FOREIGN KEY assets_models_ibfk_2');
        $this->query('ALTER TABLE monarc_common.threats_models DROP FOREIGN KEY threats_models_ibfk_1');
        $this->query('ALTER TABLE monarc_common.threats_models DROP FOREIGN KEY threats_models_ibfk_2');
        $this->query('ALTER TABLE monarc_common.vulnerabilities_models DROP FOREIGN KEY vulnerabilities_models_ibfk_1');
        $this->query('ALTER TABLE monarc_common.vulnerabilities_models DROP FOREIGN KEY vulnerabilities_models_ibfk_2');

        $table = $this->table('assets_models');
        $table
            ->addForeignKey('asset_id', 'assets', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        $table = $this->table('threats_models');
        $table
            ->addForeignKey('threat_id', 'threats', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();

        $table = $this->table('vulnerabilities_models');
        $table
            ->addForeignKey('vulnerability_id', 'vulnerabilities', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->addForeignKey('model_id', 'models', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }

}
