<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Contract;

/**
 * Additive fields exposed by the existing analysis registry.
 *
 * Mutating Scenario workflows are intentionally unavailable until their
 * dedicated deliveries. Returning explicit false values lets old and new
 * clients render only actions that can actually be completed.
 */
final class AnalysisRegistryProjection
{
    public static function getLaunchUrl(string $analysisType, int $anrId, bool $scenarioEnabled): ?string
    {
        if ($analysisType === AnalysisType::SCENARIO) {
            return $scenarioEnabled ? '/scenario/frontoffice' : null;
        }

        return '#/client/project/' . $anrId . '/anr';
    }

    /** @return array{open:bool, clone:bool, migrate:bool} */
    public static function getPermittedActions(string $analysisType, bool $scenarioEnabled): array
    {
        return [
            'open' => $analysisType !== AnalysisType::SCENARIO || $scenarioEnabled,
            'clone' => false,
            'migrate' => false,
        ];
    }
}
