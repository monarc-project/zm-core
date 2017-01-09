<?php

use Phinx\Migration\AbstractMigration;

class AnrUpdateSeuils extends AbstractMigration
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
        $this->execute('UPDATE anrs SET seuil1 = 4 WHERE seuil1 IS NULL');
        $this->execute('UPDATE anrs SET seuil2 = 8 WHERE seuil2 IS NULL');
        $this->execute('UPDATE anrs SET seuil_rolf1 = 4 WHERE seuil_rolf1 IS NULL');
        $this->execute('UPDATE anrs SET seuil_rolf2 = 8 WHERE seuil_rolf2 IS NULL');
    }
    public function down()
    {
    }
}
