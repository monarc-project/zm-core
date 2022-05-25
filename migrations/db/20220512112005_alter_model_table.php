<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class FixOpScalesTranslations extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('models');
        $table
            ->removeColumn('is_deleted')
            ->renameColumn('is_scales_updatable', "are_scales_updatable")
            ->update();
    }
}
