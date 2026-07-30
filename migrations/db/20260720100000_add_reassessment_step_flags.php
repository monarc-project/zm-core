<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;

class AddReassessmentStepFlags extends AbstractMigration
{
    public function up(): void
    {
        $this->table('anrs')
            ->addColumn('init_reassessment_strategy', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'after' => 'init_risk_context',
            ])
            ->addColumn('manage_reassessment_triggers', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'after' => 'manage_risks',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('anrs')
            ->removeColumn('manage_reassessment_triggers')
            ->removeColumn('init_reassessment_strategy')
            ->update();
    }
}
