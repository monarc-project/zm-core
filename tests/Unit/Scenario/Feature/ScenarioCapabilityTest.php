<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Scenario\Feature;

use Monarc\Core\Scenario\Feature\ScenarioCapability;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Monarc\Core\Scenario\Feature\ScenarioCapability
 */
class ScenarioCapabilityTest extends TestCase
{
    public function testOnlyApprovedServerSideIdentityModesAreAccepted(): void
    {
        $this->assertSame('legacy', (new ScenarioCapability([]))->identityMode());
        $this->assertSame('zitadel', (new ScenarioCapability(['identity' => ['mode' => 'zitadel']]))->identityMode());

        $this->expectException(\InvalidArgumentException::class);
        (new ScenarioCapability(['identity' => ['mode' => 'browser']]))->identityMode();
    }

    public function testScenarioFeatureFlagIsGlobal(): void
    {
        $this->assertFalse((new ScenarioCapability([]))->isEnabled());
        $this->assertFalse((new ScenarioCapability(['enabled' => false]))->isEnabled());
        $this->assertTrue((new ScenarioCapability(['enabled' => true]))->isEnabled());
    }
}
