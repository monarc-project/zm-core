<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Scenario\Contract;

use Monarc\Core\Scenario\Contract\ScenarioStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Monarc\Core\Scenario\Contract\ScenarioStatus
 */
class ScenarioStatusTest extends TestCase
{
    public function testHealthResponseIsPrivacySafeAndVersioned(): void
    {
        $this->assertSame([
            'status' => 'ok',
            'service' => 'scenario',
            'version' => 'v1',
            'enabled' => false,
        ], ScenarioStatus::health(false));
    }

    public function testReadinessReportsOnlyTheDependencyState(): void
    {
        $this->assertSame('ready', ScenarioStatus::readiness(true, true)['status']);
        $this->assertSame('not_ready', ScenarioStatus::readiness(false, false)['status']);
    }

}
