<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Contract;

/**
 * Privacy-safe responses shared by the versioned Scenario API probes.
 */
final class ScenarioStatus
{
    public static function health(bool $enabled): array
    {
        return [
            'status' => 'ok',
            'service' => 'scenario',
            'version' => 'v1',
            'enabled' => $enabled,
        ];
    }

    public static function readiness(bool $enabled, bool $ready): array
    {
        return [
            'status' => $ready ? 'ready' : 'not_ready',
            'service' => 'scenario',
            'version' => 'v1',
            'enabled' => $enabled,
        ];
    }
}
