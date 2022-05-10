<?php

use Phinx\Migration\AbstractMigration;

class AddCustomizableSoaScale extends AbstractMigration
{
    /**
     * Performs validation and adding of different languages missing translations of the op scales values.
     */
    public function change()
    {
        //create the scale for the SOA with the number of level
        $table = $this->table('soa_scale');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('number_of_levels', 'integer', array('null' => true, 'limit' => 255))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer', array('identity'=>true, 'signed'=>false))->update();
        $table->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))->update();

        //create the comments, one comment by level
        $table = $this->table('soa_scale_comments');
        $table
            ->addColumn('soa_scale_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('level', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('colour', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('comment_translation_key', 'string', array('null' => true, 'limit' => 7))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('soa_scale_id'))
            ->create();
        $table->changeColumn('id', 'integer', array('identity'=>true, 'signed'=>false))->update();
        $table->addForeignKey('soa_scale_id', 'soa_scale', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
