<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Scenario\Contract;

use Monarc\Core\Scenario\Contract\AnalysisType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Monarc\Core\Scenario\Contract\AnalysisType
 */
class AnalysisTypeTest extends TestCase
{
    public function testOnlySupportedAnalysisTypesAreAccepted(): void
    {
        $this->assertTrue(AnalysisType::isValid(AnalysisType::ASSET));
        $this->assertTrue(AnalysisType::isValid(AnalysisType::SCENARIO));
        $this->assertFalse(AnalysisType::isValid('amv'));
    }
}
