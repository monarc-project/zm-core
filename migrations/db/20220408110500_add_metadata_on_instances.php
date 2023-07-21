<?php

use Phinx\Migration\AbstractMigration;

class AddMetadataOnInstances extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('anr_metadatas_on_instances');
        $table
            ->addColumn('anr_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('label_translation_key', 'string', ['null' => true, 'limit' => 255])
            ->addColumn('creator', 'string', ['null' => true, 'limit' => 255])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updater', 'string', ['null' => true, 'limit' => 255])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['anr_id'])
            ->create();
        $table->changeColumn('id', 'integer', ['identity' => true, 'signed' => false])->update();
        $table->addForeignKey('anr_id', 'anrs', 'id', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])->update();
    }
}
