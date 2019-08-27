<?php

use Phinx\Migration\AbstractMigration;

class FixEnglishLabelForReferentialMeasure extends AbstractMigration
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
        $this->execute('UPDATE measures SET label2 = "Secure log-on procedures" WHERE uuid = "267fd954-f705-11e8-b555-0800279aaa2b"');
    }
}
