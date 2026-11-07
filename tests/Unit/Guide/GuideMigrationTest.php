<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Unit\Guide;

use Phinx\Db\Adapter\SQLiteAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

/** @coversNothing */
class GuideMigrationTest extends TestCase
{
    public function testCleanupPreservesContent(): void
    {
        require_once dirname(__DIR__, 3) . '/migrations/db/20261029110000_drop_unused_guide_anr_columns.php';
        $adapter = new SQLiteAdapter(['name' => ':memory:'], null, new NullOutput());
        $pdo = $adapter->getConnection();
        $pdo->exec('CREATE TABLE guides (id INTEGER PRIMARY KEY, anr_id INTEGER, description1 TEXT)');
        $pdo->exec('CREATE INDEX guides_anr ON guides (anr_id)');
        $pdo->exec('CREATE TABLE guides_items (
            id INTEGER PRIMARY KEY, anr_id INTEGER, guide_id INTEGER, description1 TEXT, position INTEGER
        )');
        $pdo->exec('CREATE INDEX items_anr ON guides_items (anr_id)');
        $pdo->exec("INSERT INTO guides VALUES (1, 8, 'Existing guide')");
        $pdo->exec("INSERT INTO guides_items VALUES (2, 8, 1, 'Existing item', 3)");
        $pdo->exec('CREATE TABLE questions (id INTEGER PRIMARY KEY)');
        $pdo->exec('CREATE TABLE questions_choices (id INTEGER PRIMARY KEY, question_id INTEGER)');
        $migration = new \DropUnusedGuideAnrColumns('testing', 20261029110000, null, new NullOutput());
        $migration->setAdapter($adapter);
        $migration->up();
        $migration->up();
        foreach (['guides', 'guides_items'] as $table) {
            self::assertFalse($adapter->hasColumn($table, 'anr_id'));
        }
        self::assertSame(
            ['id' => 1, 'description1' => 'Existing guide'],
            $pdo->query('SELECT * FROM guides')->fetch(\PDO::FETCH_ASSOC)
        );
        self::assertSame(
            ['id' => 2, 'guide_id' => 1, 'description1' => 'Existing item', 'position' => 3],
            $pdo->query('SELECT * FROM guides_items')->fetch(\PDO::FETCH_ASSOC)
        );
    }
}
