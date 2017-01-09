<?php

use Phinx\Migration\AbstractMigration;

class InstancesUpdatePositions extends AbstractMigration
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
        $this->execute('
            SET @current_parent = NULL;
            SET @current_count = NULL;
            SET @current_anr = NULL;

            UPDATE instances
            SET position = CASE
                WHEN (@current_anr = anr_id) AND (@current_parent = parent_id OR (@current_parent IS NULL AND parent_id IS NULL)) THEN @current_count := @current_count +1
                WHEN (@current_anr := anr_id) AND (@current_parent := parent_id) THEN @current_count := 1
                ELSE @current_count := 1
            END
            ORDER BY anr_id, parent_id, position, name1, name2, name3, name4;
        ');
    }
}
