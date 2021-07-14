<?php

use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;
use Monarc\Core\Model\Entity\OperationalRiskScaleType;
use Phinx\Migration\AbstractMigration;
use Ramsey\Uuid\Uuid;

class ChangeableOperationalImpact extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */

    public function change()
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `operational_risks_scales` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `anr_id` int(11) unsigned,
                `type` tinyint(3) unsigned NOT NULL DEFAULT 0,
                `min` smallint(6) unsigned NOT NULL DEFAULT 0,
                `max` smallint(6) unsigned NOT NULL DEFAULT 0,
                `creator` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                CONSTRAINT `op_risks_scales_anr_id_fk` FOREIGN KEY (`anr_id`) REFERENCES `anrs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
            );'
        );

        $this->execute(
            'CREATE TABLE IF NOT EXISTS `operational_risks_scales_types` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `anr_id` int(11) unsigned,
                `operational_risk_scale_id` int(11) unsigned NOT NULL,
                `label_translation_key` varchar(255) NOT NULL,
                `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
                `is_system` tinyint(1) NOT NULL DEFAULT 0,
                `creator` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `op_risks_scales_types_anr_id_indx` (`anr_id`),
                INDEX `op_risks_scales_types_scale_id_indx` (`operational_risk_scale_id`),
                CONSTRAINT `op_risks_scales_types_anr_id_fk` FOREIGN KEY (`anr_id`) REFERENCES `anrs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
                CONSTRAINT `op_risks_scales_types_scale_id_fk` FOREIGN KEY (`operational_risk_scale_id`) REFERENCES `operational_risks_scales` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
            );'
        );

        $this->execute(
            'CREATE TABLE IF NOT EXISTS `operational_risks_scales_comments` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `anr_id` int(11) unsigned,
                `operational_risk_scale_id` int(11) unsigned NOT NULL,
                `operational_risk_scale_type_id` int(11) unsigned,
                `scale_value` int(11) unsigned NOT NULL,
                `scale_index` smallint(6) unsigned NOT NULL,
                `comment_translation_key` varchar(255) NOT NULL,
                `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
                `creator` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `op_risks_scales_comments_anr_id_indx` (`anr_id`),
                INDEX `op_risks_scales_comments_scale_id_indx` (`operational_risk_scale_id`),
                INDEX `op_risks_scales_comments_scale_type_id_indx` (`operational_risk_scale_type_id`),
                CONSTRAINT `op_risks_scales_comments_anr_id_fk` FOREIGN KEY (`anr_id`) REFERENCES anrs (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
                CONSTRAINT `op_risks_scales_comments_scale_id_fk` FOREIGN KEY (`operational_risk_scale_id`) REFERENCES `operational_risks_scales` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
                CONSTRAINT `op_risks_scales_comments_scale_type_id_fk` FOREIGN KEY (`operational_risk_scale_type_id`) REFERENCES `operational_risks_scales_types` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
            );'
        );

        $this->execute(
            'CREATE TABLE IF NOT EXISTS `operational_instance_risks_scales` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `anr_id` int(11) unsigned,
                `instance_risk_op_id` int(11) unsigned NOT NULL,
                `operational_risk_scale_type_id` int(11) unsigned NOT NULL,
                `brut_value` int(11) NOT NULL DEFAULT -1,
                `net_value` int(11) NOT NULL DEFAULT -1,
                `targeted_value` int(11) NOT NULL DEFAULT -1,
                `creator` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `oirs_instance_risk_op_id_indx` (`instance_risk_op_id`),
                INDEX `oirs_op_risk_scale_type_id_indx` (`operational_risk_scale_type_id`),
                UNIQUE `oirs_anr_id_instance_risk_op_id_op_risk_scale_id_unq` (`anr_id`, `instance_risk_op_id`, `operational_risk_scale_type_id`),
                CONSTRAINT `op_instance_risks_scales_anr_id_fk` FOREIGN KEY (`anr_id`) REFERENCES anrs (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
                CONSTRAINT `oirs_instance_risk_op_id_fk` FOREIGN KEY (`instance_risk_op_id`) REFERENCES `instances_risks_op` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
                CONSTRAINT `oirs_operational_risk_scale_type_id_fk` FOREIGN KEY (`operational_risk_scale_type_id`) REFERENCES `operational_risks_scales_types` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
            );'
        );

        $this->execute(
            'CREATE TABLE IF NOT EXISTS `translations` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `anr_id` int(11) unsigned,
                `type` varchar(255) NOT NULL,
                `translation_key` varchar(255) NOT NULL,
                `lang` char(2) NOT NULL,
                `value` TEXT,
                `creator` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updater` varchar(255) DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `translations_key_indx` (`translation_key`),
                INDEX `translations_type_indx` (`type`),
                UNIQUE `translations_key_lang_unq` (`anr_id`, `translation_key`, `lang`),
                CONSTRAINT `translations_anr_id_fk` FOREIGN KEY (`anr_id`) REFERENCES anrs (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
            );'
        );

        // Migration of scales, impact types and operational risks values.
        $scalesQuery = $this->query(
            'SELECT s.anr_id, s.type AS scale_type, sit.label1, sit.label2, sit.label3, sit.label4, s.min, s.max,
                  GROUP_CONCAT(sc.val SEPARATOR "-----") as scale_values,
                  GROUP_CONCAT(sc.comment1 SEPARATOR "-----") comments1,
                  GROUP_CONCAT(sc.comment2 SEPARATOR "-----") comments2,
                  GROUP_CONCAT(sc.comment3 SEPARATOR "-----") comments3,
                  GROUP_CONCAT(sc.comment4 SEPARATOR "-----") comments4,
                  sit.type AS scale_impact_type, sit.is_sys, sit.is_hidden
          FROM scales s
            INNER JOIN scales_comments sc ON sc.scale_id = s.id
            LEFT JOIN scales_impact_types sit ON sit.scale_id = s.id AND sit.id = sc.scale_type_impact_id
          WHERE s.type = 1 AND sit.type > 3 OR s.type = 2
          GROUP BY s.anr_id, s.id, sit.id
          ORDER BY s.anr_id, s.id'
        );

        $operationalRisksScalesTable = $this->table('operational_risks_scales');
        $operationalRisksScalesTypesTable = $this->table('operational_risks_scales_types');
        $operationalRisksScalesCommentsTable = $this->table('operational_risks_scales_comments');
        $currentScaleTypesByAnr = [];
        $currentScalesByAnrAndType = [];
        foreach ($scalesQuery->fetchAll() as $scaleData) {
            $isLikelihoodScale = (int)$scaleData['scale_type'] === OperationalRiskScale::TYPE_LIKELIHOOD;
            $scaleType = $isLikelihoodScale ? OperationalRiskScale::TYPE_LIKELIHOOD : OperationalRiskScale::TYPE_IMPACT;
            if (!isset($currentScalesByAnrAndType[$scaleData['anr_id']][$scaleType])) {
                $operationalRisksScalesTable->insert([
                    'anr_id' => $scaleData['anr_id'],
                    'type' => $scaleType,
                    'min' => 0,
                    'max' => (int)$scaleData['max'] - (int)$scaleData['min'],
                    'creator' => 'Migration script',
                ])->save();
                $currentScalesByAnrAndType[$scaleData['anr_id']][$scaleType] = $this->getAdapter()->getConnection()->lastInsertId();
            }

            $operationalRiskScaleTypeId = null;
            if (!$isLikelihoodScale) {
                $labelTranslationKey = (string)Uuid::uuid4();
                $operationalRisksScalesTypesTable->insert([
                    'anr_id' => $scaleData['anr_id'],
                    'operational_risk_scale_id' => $currentScalesByAnrAndType[$scaleData['anr_id']][$scaleType],
                    'label_translation_key' => $labelTranslationKey,
                    'is_system' => $scaleData['is_sys'],
                    'is_hidden' => $scaleData['is_hidden'],
                    'creator' => 'Migration script',
                ])->save();
                $operationalRiskScaleTypeId = $this->getAdapter()->getConnection()->lastInsertId();
                $this->createTranslations($scaleData, OperationalRiskScaleType::TRANSLATION_TYPE_NAME, 'label', $labelTranslationKey);
            }

            $scaleValues = explode('-----', $scaleData['scale_values']);
            $comments1 = explode('-----', $scaleData['comments1']);
            $comments2 = explode('-----', $scaleData['comments2']);
            $comments3 = explode('-----', $scaleData['comments3']);
            $comments4 = explode('-----', $scaleData['comments4']);
            $index = 0;
            foreach ($scaleValues as $valueKey => $scaleValue) {
                if($scaleData['min'] <= $scaleValue) {
                    $commentTranslationKey = Uuid::uuid4();
                    $operationalRisksScalesCommentsTable->insert([
                        'anr_id' => $scaleData['anr_id'],
                        'operational_risk_scale_id' => $currentScalesByAnrAndType[$scaleData['anr_id']][$scaleType],
                        'operational_risk_scale_type_id' => $operationalRiskScaleTypeId,
                        'scale_value' => $scaleValue,
                        'scale_index' => $index++,
                        'is_hidden' => $scaleData['max'] < $scaleValue ? 1 : 0,
                        'comment_translation_key' => $commentTranslationKey,
                        'creator' => 'Migration script',
                    ])->save();
                    $this->createTranslations(
                        [
                            'anr_id' => $scaleData['anr_id'],
                            'comment1' => $comments1[$valueKey] ?? '',
                            'comment2' => $comments2[$valueKey] ?? '',
                            'comment3' => $comments3[$valueKey] ?? '',
                            'comment4' => $comments4[$valueKey] ?? '',
                        ],
                        OperationalRiskScaleComment::TRANSLATION_TYPE_NAME,
                        'comment',
                        $commentTranslationKey
                    );
                }
            }

            if (!empty($currentScaleTypesByAnr) && array_key_first($currentScaleTypesByAnr) !== $scaleData['anr_id']) {
                // @jerome: 4:R 5:O 6:L 7:F 8:P -- easier to migrate instances_risks_op > 8 = custom
                $this->createOperationalInstanceRisksScales($currentScaleTypesByAnr);
                $currentScaleTypesByAnr = [];
            }

            if ($operationalRiskScaleTypeId !== null) {
                $currentScaleTypesByAnr[$scaleData['anr_id']][(int)$scaleData['scale_impact_type']] = $operationalRiskScaleTypeId;
            }
        }

        if (!empty($currentScaleTypesByAnr)) {
            $this->createOperationalInstanceRisksScales($currentScaleTypesByAnr);
        }

        // Migration for table scales_comments
        $table = $this->table('scales_comments');
        $table
            ->renameColumn('val', 'scale_value')
            ->addColumn('scale_index', 'integer', ['null' => true, 'signed' => false, 'after' => 'scale_value'])
            ->update();

        $this->execute('update scales_comments set scale_index = scale_value');

        // Remove the deprecated columns from instances_risks_op.
//        $this->table('instances_risks_op')
//            ->removeColumn('brut_r')
//            ->removeColumn('brut_o')
//            ->removeColumn('brut_l')
//            ->removeColumn('brut_f')
//            ->removeColumn('brut_p')
//            ->removeColumn('net_r')
//            ->removeColumn('net_o')
//            ->removeColumn('net_l')
//            ->removeColumn('net_f')
//            ->removeColumn('net_p')
//            ->removeColumn('targeted_r')
//            ->removeColumn('targeted_o')
//            ->removeColumn('targeted_l')
//            ->removeColumn('targeted_f')
//            ->removeColumn('targeted_p')
//            ->update();
    }

    private function createTranslations(array $data, string $type, string $fieldName, string $translationKey): void
    {
        $translations = [];
        foreach ([1 => 'fr', 2 => 'en', 3 => 'de', 4 => 'nl'] as $langKey => $langLabel) {
            if (!empty($data[$fieldName . $langKey])) {
                $translations[] = [
                    'anr_id' => $data['anr_id'],
                    'type' => $type,
                    'translation_key' => $translationKey,
                    'lang' => $langLabel,
                    'value' => $data[$fieldName . $langKey],
                    'creator' => 'Migration script',
                ];
            }
        }
        $this->table('translations')->insert($translations)->save();
    }

    private function createOperationalInstanceRisksScales(array $currentScaleTypesByAnr): void
    {
        $operationalInstanceRisksScalesTable = $this->table('operational_instance_risks_scales');
        $anrId = array_key_first($currentScaleTypesByAnr);
        $instanceRisksOpSqlWithAnr = sprintf(
            'SELECT id, anr_id,
                    brut_r, brut_o, brut_l, brut_f, brut_p,
                    net_r, net_o, net_l, net_f, net_p,
                    targeted_r, targeted_o, targeted_l, targeted_f, targeted_p
            FROM instances_risks_op
            WHERE anr_id = %d',
            $anrId
        );
        $impactTypes = [4 => '_r', 5 => '_o', 6 => '_l', 7 => '_f', 8 => '_p'];
        foreach ($this->query($instanceRisksOpSqlWithAnr)->fetchAll() as $instancesRisksOp) {
            $operationalInstanceRisksScales = [];
            foreach ($currentScaleTypesByAnr[$anrId] as $scaleImpactType => $operationalRiskScaleTypeId) {
                if ($scaleImpactType !== OperationalRiskScale::TYPE_IMPACT) {
                    continue;
                }
                $isSystemScaleImpactType = isset($impactTypes[$scaleImpactType]);
                $operationalInstanceRisksScales[] = [
                    'anr_id' => $anrId,
                    'instance_risk_op_id' => $instancesRisksOp['id'],
                    'operational_risk_scale_type_id' => $operationalRiskScaleTypeId,
                    'brut_value' => $isSystemScaleImpactType
                        ? $instancesRisksOp['brut' . $impactTypes[$scaleImpactType]]
                        : -1,
                    'net_value' => $isSystemScaleImpactType
                        ? $instancesRisksOp['net' . $impactTypes[$scaleImpactType]]
                        : -1,
                    'targeted_value' => $isSystemScaleImpactType
                        ? $instancesRisksOp['targeted' . $impactTypes[$scaleImpactType]]
                        : -1,
                    'creator' => 'Migration script',
                ];
            }
            $operationalInstanceRisksScalesTable->insert($operationalInstanceRisksScales)->save();
        }
    }
}
