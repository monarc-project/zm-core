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
            if ($expectedCompositionLinkPosition !== $compositionObjectsData['position']) {
                $this->execute(sprintf(
                    'UPDATE objects_objects SET position = %d WHERE id = %d',
                    $expectedCompositionLinkPosition,
                    $compositionObjectsData['id']
                ));
            }

            $expectedCompositionLinkPosition++;
            $previousParentObjectId = $compositionObjectsData['father_id'];
        }

        /* Fix instances positions to have them in a correct sequence (1, 2, 3, ...). */
        $instancesQuery = $this->query(
            'SELECT id, anr_id, parent_id, position FROM instances ORDER BY anr_id, parent_id, position'
        );
        $previousParentInstanceId = null;
        $expectedInstancePosition = 1;
        foreach ($instancesQuery->fetchAll() as $instanceData) {
            if ($previousParentInstanceId === null) {
                $previousParentInstanceId = (int)$instanceData['parent_id'];
            }
            if ((int)$instanceData['parent_id'] !== $previousParentInstanceId) {
                $expectedInstancePosition = 1;
            }
            if ($expectedInstancePosition !== $instanceData['position']) {
                $this->execute(sprintf(
                    'UPDATE instances SET position = %d WHERE id = %d',
                    $expectedInstancePosition,
                    $instanceData['id']
                ));
            }

            $expectedInstancePosition++;
            $previousParentInstanceId = (int)$instanceData['parent_id'];
        }

        /* The position of the category will be used based on object_category table (root_id = null). */
        $this->table('anrs_objects_categories')
            ->removeColumn('position')
            ->removeColumn('creator')
            ->removeColumn('created_at')
            ->removeColumn('updater')
            ->removeColumn('updated_at')
            ->update();

        $table = $this->table('anr_metadatas_on_instances');
        $table->rename('instances_metadata_fields')->update();

        $this->execute(
            'update translations set type = "instances-metadata-fields" where type = "anr-metadatas-on-instances"'
        );

        $this->table('instances')->removeColumn('disponibility')->update();
        $this->table('objects')->removeColumn('disponibility')->update();

        $this->table('instances_consequences')->removeColumn('object_id')->removeColumn('locally_touched')->update();
    }
}
