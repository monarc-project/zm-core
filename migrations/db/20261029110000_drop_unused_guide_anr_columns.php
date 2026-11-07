<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace -- Phinx discovers global migration classes.
class DropUnusedGuideAnrColumns extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('DELETE FROM questions_choices WHERE question_id IS NULL');
        $this->table('questions_choices')
            ->changeColumn('question_id', 'integer', ['null' => false, 'signed' => false])
            ->update();

        $this->table('guides_items')
            ->changeColumn('guide_id', 'integer', ['null' => false, 'signed' => false])
            ->update();

        foreach (['guides_items', 'guides'] as $name) {
            $table = $this->table($name);
            if (!$table->hasColumn('anr_id')) {
                continue;
            }
            if ($table->hasForeignKey('anr_id')) {
                $table->dropForeignKey('anr_id')->update();
            }
            if ($table->hasIndex(['anr_id'])) {
                $table->removeIndex(['anr_id'])->update();
            }
            $table->removeColumn('anr_id')->update();
        }
    }

    public function down(): void
    {
        throw new IrreversibleMigrationException('Removed unused guide analysis identifiers cannot be restored.');
    }
}
