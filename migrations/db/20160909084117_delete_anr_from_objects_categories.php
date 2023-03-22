<?php

use Phinx\Migration\AbstractMigration;

class DeleteAnrFromObjectsCategories extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('objects_categories');
        $exists = $table->hasForeignKey('anr_id');
        if ($exists) {
            $table->dropForeignKey('anr_id');
        }
        $table->removeColumn('anr_id')->update();
    }
}
