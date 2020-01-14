<?php

use Phinx\Migration\AbstractMigration;

class FixTextFieldsConsistency extends AbstractMigration
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
            'ALTER TABLE `instances_risks` CHANGE `comment` `comment` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `comment_after` `comment_after` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
            ALTER TABLE `instances_risks_op` CHANGE `risk_cache_description1` `risk_cache_description1` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `risk_cache_description2` `risk_cache_description2` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `risk_cache_description3` `risk_cache_description3` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `risk_cache_description4` `risk_cache_description4` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `comment` `comment` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `mitigation` `mitigation` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
            ALTER TABLE `soacategory` CHANGE `label1` `label1` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `label2` `label2` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `label3` `label3` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `label4` `label4` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
            ALTER TABLE `anrs` CHANGE `description1` `description1` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `description2` `description2` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `description3` `description3` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `description4` `description4` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `context_ana_risk` `context_ana_risk` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `context_gest_risk` `context_gest_risk` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `synth_threat` `synth_threat` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                CHANGE `synth_act` `synth_act` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
            '
        );
    }
}
