<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Scenario\Contract;

use Monarc\Core\Scenario\Contract\ScenarioAnalysisLifecycle;
use PHPUnit\Framework\TestCase;

/** @covers \Monarc\Core\Scenario\Contract\ScenarioAnalysisLifecycle */
class ScenarioAnalysisLifecycleTest extends TestCase
{
    public function testAllowsOnlyApprovedLifecycleTransitions(): void
    {
        $this->assertTrue(ScenarioAnalysisLifecycle::canTransition('draft', 'active'));
        $this->assertTrue(ScenarioAnalysisLifecycle::canTransition('active', 'monitored'));
        $this->assertTrue(ScenarioAnalysisLifecycle::canTransition('closed', 'archived'));
        $this->assertFalse(ScenarioAnalysisLifecycle::canTransition('archived', 'active'));
        $this->assertFalse(ScenarioAnalysisLifecycle::canTransition('draft', 'closed'));
    }
}
