<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Unit\Runtime;

use Monarc\Core\Tests\Runtime\MappingBaseline;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class MappingBaselineTest extends TestCase
{
    public function testRemovingAnExistingDiagnosticDoesNotRequireKeepingTheDefect(): void
    {
        self::assertSame([], MappingBaseline::unexpectedErrors(
            ['EntityA' => ['missing inverse']],
            ['EntityA' => ['missing inverse', 'invalid target'], 'EntityB' => ['invalid field']]
        ));
    }

    public function testNewDiagnosticOnAnExistingEntityIsRejected(): void
    {
        self::assertSame(['EntityA' => ['invalid target']], MappingBaseline::unexpectedErrors(
            ['EntityA' => ['missing inverse', 'invalid target']],
            ['EntityA' => ['missing inverse']]
        ));
    }

    public function testDiagnosticFromAnotherEntityCannotAuthorizeANewDefect(): void
    {
        self::assertSame(['EntityB' => ['missing inverse']], MappingBaseline::unexpectedErrors(
            ['EntityB' => ['missing inverse']],
            ['EntityA' => ['missing inverse']]
        ));
    }

    public function testCleanMappingsNeedNoBaselineExceptions(): void
    {
        self::assertSame([], MappingBaseline::unexpectedErrors([], []));
    }
}
