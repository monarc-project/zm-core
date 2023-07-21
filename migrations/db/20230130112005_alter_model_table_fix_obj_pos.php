<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;

class AlterModelTableFixObjPos extends AbstractMigration
{
    public function change()
    {
        $this->table('models')
            ->removeColumn('is_deleted')
            ->renameColumn('is_scales_updatable', 'are_scales_updatable')
            ->dropForeignKey('anr_id')
            ->addForeignKey('anr_id', 'anrs', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->update();

        $this->execute('ALTER TABLE `amvs` MODIFY updated_at datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;');

        /* Fix the amvs positions. */
        $amvsQuery = $this->query('SELECT uuid, asset_id, position FROM `amvs` ORDER BY asset_id, position');
        $previousAssetUuid = null;
        $expectedAmvPosition = 1;
        foreach ($amvsQuery->fetchAll() as $amvData) {
            if ($previousAssetUuid === null) {
                $previousAssetUuid = $amvData['asset_id'];
            }
            if ($amvData['asset_id'] !== $previousAssetUuid) {
                $expectedAmvPosition = 1;
            }
            if ($expectedAmvPosition !== $amvData['position']) {
                $this->execute(
                    sprintf(
                        'UPDATE amvs SET position = %d WHERE uuid = "%s"',
                        $expectedAmvPosition,
                        $amvData['uuid']
                    )
                );
            }

            $expectedAmvPosition++;
            $previousAssetUuid = $amvData['asset_id'];
        }

        /* Fix the objects positions. */
        $objectsQuery = $this->query(
            'SELECT uuid, object_category_id, position FROM objects ORDER BY object_category_id, position'
        );
        $previousObjectCategoryId = null;
        $expectedObjectPosition = 1;
        foreach ($objectsQuery->fetchAll() as $objectData) {
            if ($previousObjectCategoryId === null) {
                $previousObjectCategoryId = $objectData['object_category_id'];
            }
            if ($objectData['object_category_id'] !== $previousObjectCategoryId) {
                $expectedObjectPosition = 1;
            }
            if ($expectedObjectPosition !== $objectData['position']) {
                $this->execute(
                    sprintf(
                        'UPDATE objects SET position = %d WHERE uuid = "%s"',
                        $expectedObjectPosition,
                        $objectData['uuid']
                    )
                );
            }

            $expectedObjectPosition++;
            $previousObjectCategoryId = $objectData['object_category_id'];
        }

        /* Fix the objects compositions positions. */
        $objectsObjectsQuery = $this->query(
            'SELECT id, father_id, position FROM objects_objects ORDER BY father_id, position'
        );
        $previousParentObjectUuid = null;
        $expectedCompositionLinkPosition = 1;
        foreach ($objectsObjectsQuery->fetchAll() as $objectCompositionData) {
            if ($previousParentObjectUuid === null) {
                $previousParentObjectUuid = $objectCompositionData['father_id'];
            }
            if ($objectCompositionData['father_id'] !== $previousParentObjectUuid) {
                $expectedCompositionLinkPosition = 1;
            }
            if ($expectedCompositionLinkPosition !== $objectCompositionData['position']) {
                $this->execute(
                    sprintf(
                        'UPDATE objects_objects SET position = %d WHERE id = %d',
                        $expectedCompositionLinkPosition,
                        $objectCompositionData['id']
                    )
                );
            }

            $expectedCompositionLinkPosition++;
            $previousParentObjectUuid = $objectCompositionData['father_id'];
        }

        /* Fix the objects categories positions. */
        $objectsCategoriesQuery = $this->query(
            'SELECT id, parent_id, position FROM objects_categories ORDER BY parent_id, position'
        );
        $previousParentCategoryId = -1;
        $expectedCategoryPosition = 1;
        foreach ($objectsCategoriesQuery->fetchAll() as $objectCategoryData) {
            if ($previousParentCategoryId === -1) {
                $previousParentCategoryId = $objectCategoryData['parent_id'];
            }
            if ($objectCategoryData['parent_id'] !== $previousParentCategoryId) {
                $expectedCategoryPosition = 1;
            }
            if ($expectedCategoryPosition !== $objectCategoryData['position']) {
                $this->execute(
                    sprintf(
                        'UPDATE objects_categories SET position = %d WHERE id = %d',
                        $expectedCategoryPosition,
                        $objectCategoryData['id']
                    )
                );
            }

            $expectedCategoryPosition++;
            $previousParentCategoryId = $objectCategoryData['parent_id'];
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
                $this->execute(
                    sprintf(
                        'UPDATE instances SET position = %d WHERE id = %d',
                        $expectedInstancePosition,
                        $instanceData['id']
                    )
                );
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

        $this->execute(
            'update translations set type = "instances-metadata-fields" where type = "anr-metadatas-on-instances"'
        );

        $this->table('instances')->removeColumn('disponibility')->update();
        $this->table('objects')
            ->removeColumn('disponibility')
            ->removeColumn('token_import')
            ->removeColumn('original_name')
            ->update();

        $this->table('instances_consequences')->removeColumn('object_id')->removeColumn('locally_touched')->update();

        $this->table('historicals')
            ->changeColumn('source_id', 'string', ['null' => true, 'limit' => 64])
            ->update();

        /* Fix possibly missing soacategory. */
        $measuresQuery = $this->query('SELECT uuid, referential_uuid FROM measures WHERE soacategory_id IS NULL;');
        $soaCategoryId = null;
        $soaCategoryTable = $this->table('soacategory');
        foreach ($measuresQuery->fetchAll() as $measureData) {
            if ($soaCategoryId === null) {
                $soaCategoryTable->insert([
                    'label1' => 'catégorie manquante',
                    'label2' => 'missing category',
                    'label3' => 'fehlende Kategorie',
                    'label4' => 'ontbrekende categorie',
                    'referential_uuid' => $measureData['referential_uuid'],
                ])->saveData();
                $soaCategoryId = (int)$this->getAdapter()->getConnection()->lastInsertId();
            }

            $this->execute(
                'UPDATE measures SET soacategory_id = ' . $soaCategoryId
                . ' WHERE uuid = "' . $measureData['uuid'] . '"'
            );
        }

        /* Rename the `anr_metadatas_on_instances` to `anr_instance_metadata_fields`. */
        $this->table('anr_metadatas_on_instances')->rename('anr_instance_metadata_fields')->update();
        $this->table('anr_instance_metadata_fields')
            ->changeColumn('anr_id', 'integer', ['signed' => false, 'null' => false])
            ->changeColumn('label_translation_key', 'string', ['null' => false, 'limit' => 255])
            ->update();
    }
}
