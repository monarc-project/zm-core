<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AlterModelTableFixObjPos extends AbstractMigration
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

        /* Fix the objects compositions positions. */
        $objectsQuery = $this->query(
            'SELECT id, father_id, position FROM objects_objects ORDER BY father_id, position'
        );
        $previousParentObjectId = null;
        $expectedCompositionLinkPosition = 1;
        foreach ($objectsQuery->fetchAll() as $compositionObjectsData) {
            if ($previousParentObjectId === null) {
                $previousParentObjectId = $compositionObjectsData['father_id'];
            }
            if ($compositionObjectsData['father_id'] !== $previousParentObjectId) {
                $expectedCompositionLinkPosition = 1;
            }
            if ($compositionObjectsData['father_id'] === $previousParentObjectId
                && $expectedCompositionLinkPosition !== $compositionObjectsData['position']
            ) {
                $this->execute(sprintf(
                    'UPDATE objects_objects SET position = %d WHERE id = %d',
                    $expectedCompositionLinkPosition,
                    $compositionObjectsData['id']
                ));
            }

            $expectedCompositionLinkPosition++;
            $previousParentObjectId = $compositionObjectsData['father_id'];
        }

        $table = $this->table('anr_metadatas_on_instances');
        $table->rename('instances_metadata')->update();

        $this->execute('update translations set type = "instance-metadata" where type = "anr-metadatas-on-instances"');
    }
}
