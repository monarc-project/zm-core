<?php declare(strict_types=1);

namespace Monarc\Core\Tests\Unit\Scenario\Contract;

use Monarc\Core\Scenario\Contract\AnalysisRegistryProjection;
use Monarc\Core\Scenario\Contract\AnalysisType;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Monarc\Core\Scenario\Contract\AnalysisRegistryProjection
 */
class AnalysisRegistryProjectionTest extends TestCase
{
    public function testAssetProjectionKeepsItsLegacyLaunchRoute(): void
    {
        self::assertSame(
            '#/client/project/12/anr',
            AnalysisRegistryProjection::getLaunchUrl(AnalysisType::ASSET, 12, false)
        );
        self::assertSame(
            ['open' => true, 'clone' => false, 'migrate' => false],
            AnalysisRegistryProjection::getPermittedActions(AnalysisType::ASSET, false)
        );
    }

    public function testScenarioProjectionUsesTheFrontOfficeBridgeRouteWhenEnabled(): void
    {
        self::assertSame(
            '/scenario/frontoffice',
            AnalysisRegistryProjection::getLaunchUrl(AnalysisType::SCENARIO, 34, true)
        );
        self::assertSame(
            ['open' => true, 'clone' => false, 'migrate' => false],
            AnalysisRegistryProjection::getPermittedActions(AnalysisType::SCENARIO, true)
        );
    }

    public function testScenarioProjectionIsUnavailableWhenDisabledAndHasNoMutationActions(): void
    {
        self::assertNull(AnalysisRegistryProjection::getLaunchUrl(AnalysisType::SCENARIO, 34, false));
        self::assertSame(
            ['open' => false, 'clone' => false, 'migrate' => false],
            AnalysisRegistryProjection::getPermittedActions(AnalysisType::SCENARIO, false)
        );

        // A read-only user may open an available analysis but never sees an
        // unimplemented mutation action.
        self::assertSame(
            ['open' => true, 'clone' => false, 'migrate' => false],
            AnalysisRegistryProjection::getPermittedActions(AnalysisType::SCENARIO, true)
        );
    }
}
