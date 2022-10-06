<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AlterModelTable extends AbstractMigration
{
    public function change()
    {
        $this->table('models')
            ->removeColumn('is_deleted')
            ->renameColumn('is_scales_updatable', "are_scales_updatable")
            ->dropForeignKey('anr_id')
            ->addForeignKey('anr_id', 'anrs', 'id', ['delete'=> 'SET_NULL', 'update'=> 'CASCADE'])
            ->update();

        $this->execute('ALTER TABLE amvs MODIFY updated_at datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;');

        $this->table('historicals')
            ->changeColumn('source_id', 'string', ['null' => true, 'limit' => 64])
            ->update();
    }
}
