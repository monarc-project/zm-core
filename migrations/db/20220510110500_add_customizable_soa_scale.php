<?php

use Phinx\Migration\AbstractMigration;

class AddCustomizableSoaScale extends AbstractMigration
{
    /**
     * Performs validation and adding of different languages missing translations of the op scales values.
     */
    public function change()
    {
        //create the comments, one comment by scale_index
        $table = $this->table('soa_scale_comments');
        $table
            ->addColumn('anr_id', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('scale_index', 'integer', array('null' => true, 'signed' => false))
            ->addColumn('colour', 'string', array('null' => true, 'limit' => 7))
            ->addColumn('comment_translation_key', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('is_hidden', 'boolean', array('default' => false, 'null' => false))
            ->addColumn('creator', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('created_at', 'datetime', array('null' => true))
            ->addColumn('updater', 'string', array('null' => true, 'limit' => 255))
            ->addColumn('updated_at', 'datetime', array('null' => true))
            ->addIndex(array('anr_id'))
            ->create();
        $table->changeColumn('id', 'integer', array('identity'=>true, 'signed'=>false))->update();
        $table->addForeignKey('anr_id', 'anrs', 'id', array('delete' => 'CASCADE','update' => 'RESTRICT'))
            ->update();
    }
}
