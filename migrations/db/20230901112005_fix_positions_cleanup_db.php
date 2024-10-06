<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class FixPositionsCleanupDb extends AbstractMigration
{
    public function change()
    {
        /* Cleanup and update data types. */
        $this->table('models')
            ->removeColumn('is_deleted')
            ->removeColumn('is_regulator')
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

        /* Clean up unused columns. */
        $this->table('instances')
            ->removeColumn('disponibility')
            ->removeColumn('asset_type')
            ->removeColumn('exportable')
            ->update();
        $this->table('objects')
            ->removeColumn('disponibility')
            ->removeColumn('token_import')
            ->removeColumn('original_name')
            ->removeColumn('position')
            ->dropForeignKey('anr_id')
            ->removeColumn('anr_id')
            ->update();
        $this->table('instances_consequences')->removeColumn('object_id')->removeColumn('locally_touched')->update();

        /* Fix the data type. */
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
        /* Correct MeasuresMeasures table structure. */
        $this->table('measures_measures')
            ->addColumn('id', 'integer', ['signed' => false, 'after' => MysqlAdapter::FIRST])
            ->dropForeignKey('child_id')
            ->dropForeignKey('father_id')
            ->renameColumn('father_id', 'master_measure_id')
            ->renameColumn('child_id', 'linked_measure_id')
            ->removeColumn('creator')
            ->removeColumn('created_at')
            ->removeColumn('updater')
            ->removeColumn('updated_at')
            ->update();
        $this->execute('SET @a = 0; UPDATE measures_measures SET id = @a := @a + 1;');
        $this->table('measures_measures')
            ->changePrimaryKey(['id'])
            ->addIndex(['master_measure_id', 'linked_measure_id'], ['unique' => true])
            ->update();
        $this->table('measures_measures')
            ->changeColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addForeignKey('master_measure_id', 'measures', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->addForeignKey('linked_measure_id', 'measures', 'uuid', ['delete' => 'CASCADE', 'update' => 'RESTRICT'])
            ->update();

        /* Rename the `anr_metadatas_on_instances` to `anr_instance_metadata_fields`. */
        $this->table('anr_metadatas_on_instances')->rename('anr_instance_metadata_fields')->update();
        $this->table('anr_instance_metadata_fields')
            ->changeColumn('anr_id', 'integer', ['signed' => false, 'null' => false])
            ->changeColumn('label_translation_key', 'string', ['null' => false, 'limit' => 255])
            ->update();
        $this->execute(
            'update translations set type = "anr-instance-metadata-field" where type = "anr-metadatas-on-instances"'
        );

        /* Remove table instances_risks_owners and the relation with it. */
        $this->table('instances_risks')
            ->dropForeignKey('owner_id')
            ->removeColumn('owner_id')
            ->removeColumn('context')
            ->update();
        $this->table('instances_risks_op')
            ->dropForeignKey('owner_id')
            ->removeColumn('owner_id')
            ->removeColumn('context')
            ->removeColumn('brut_r')
            ->removeColumn('brut_o')
            ->removeColumn('brut_l')
            ->removeColumn('brut_f')
            ->removeColumn('brut_p')
            ->removeColumn('net_r')
            ->removeColumn('net_o')
            ->removeColumn('net_l')
            ->removeColumn('net_f')
            ->removeColumn('net_p')
            ->removeColumn('targeted_r')
            ->removeColumn('targeted_o')
            ->removeColumn('targeted_l')
            ->removeColumn('targeted_f')
            ->removeColumn('targeted_p')
            ->update();
        $this->table('instance_risk_owners')->drop()->update();

        /* Rename the translation related fields to align the names. */
        $this->table('soa_scale_comments')
            ->renameColumn('comment_translation_key', 'label_translation_key')
            ->update();
        $this->table('operational_risks_scales_comments')
            ->renameColumn('comment_translation_key', 'label_translation_key')
            ->update();

        /* The unique relation is not correct as it should be possible to instantiate the same operational risk. */
        $this->table('operational_instance_risks_scales')
            ->removeIndex(['anr_id', 'instance_risk_op_id', 'operational_risk_scale_type_id'])
            ->addIndex(['anr_id', 'instance_risk_op_id', 'operational_risk_scale_type_id'], ['unique' => false])
            ->update();

        $this->table('scales_impact_types')
            ->removeColumn('position')
            ->update();

        /** Remove unused `anr_id` from the tables. */
        $this->table('amvs')->dropForeignKey('anr_id')->removeColumn('anr_id')->update();
        $this->table('assets')
            ->dropForeignKey('anr_id')
            ->removeIndex(['anr_id', 'code'])
            ->removeColumn('anr_id')
            ->save();
        $this->table('threats')
            ->dropForeignKey('anr_id')
            ->removeIndex(['anr_id', 'code'])
            ->removeColumn('anr_id')
            ->save();
        $this->table('themes')
            ->dropForeignKey('anr_id')
            ->removeColumn('anr_id')
            ->save();
        $this->table('objects_objects')
            ->addIndex(['father_id', 'child_id'], ['unique' => true])
            ->dropForeignKey('anr_id')
            ->removeColumn('anr_id')
            ->save();
        $this->table('objects_categories')
            ->dropForeignKey('anr_id')
            ->removeColumn('anr_id')
            ->changeColumn('label1', 'string', ['null' => false, 'default' => '', 'limit' => 2048])
            ->changeColumn('label2', 'string', ['null' => false, 'default' => '', 'limit' => 2048])
            ->changeColumn('label3', 'string', ['null' => false, 'default' => '', 'limit' => 2048])
            ->changeColumn('label4', 'string', ['null' => false, 'default' => '', 'limit' => 2048])
            ->save();
        $this->table('vulnerabilities')
            ->dropForeignKey('anr_id')
            ->removeIndex(['anr_id', 'code'])
            ->removeColumn('anr_id')
            ->save();
        $this->table('rolf_tags')
            ->dropForeignKey('anr_id')
            ->removeIndex(['anr_id', 'code'])
            ->removeColumn('anr_id')
            ->save();
        $this->table('rolf_tags')->addIndex(['code'], ['unique' => true])->save();
        $this->table('rolf_risks')
            ->dropForeignKey('anr_id')
            ->removeIndex(['anr_id', 'code'])
            ->removeColumn('anr_id')
            ->save();
        $this->table('rolf_risks')->addIndex(['code'], ['unique' => true])->save();
        $this->table('rolf_risks_tags')
            ->removeColumn('creator')
            ->removeColumn('created_at')
            ->removeColumn('updater')
            ->removeColumn('updated_at')
            ->save();
        $this->table('measures_amvs')
            ->removeColumn('creator')
            ->removeColumn('created_at')
            ->removeColumn('updater')
            ->removeColumn('updated_at')
            ->save();
        $this->table('measures_rolf_risks')
            ->dropForeignKey('anr_id')
            ->removeIndex(['anr_id'])
            ->removeColumn('anr_id')
            ->removeColumn('creator')
            ->removeColumn('created_at')
            ->removeColumn('updater')
            ->removeColumn('updated_at')
            ->save();
    }
}
