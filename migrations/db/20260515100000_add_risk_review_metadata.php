<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;

class AddRiskReviewMetadata extends AbstractMigration
{
    public function up(): void
    {
        $this->table('instances_risks')
            ->addColumn('last_review_date', 'date', ['null' => true, 'after' => 'comment_after'])
            ->addColumn('review_frequency', 'string', ['limit' => 50, 'null' => true, 'after' => 'last_review_date'])
            ->update();

        $this->table('anr_reassessment_triggers')
            ->addColumn('monitoring_approach', 'text', ['null' => true, 'after' => 'description'])
            ->update();
    }

    public function down(): void
    {
        $this->table('instances_risks')
            ->removeColumn('review_frequency')
            ->removeColumn('last_review_date')
            ->update();

        $this->table('anr_reassessment_triggers')
            ->removeColumn('monitoring_approach')
            ->update();
    }
}
