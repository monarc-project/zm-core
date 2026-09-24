<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Service;

/** Keeps legacy authentication independent when an office has no Scenario domain. */
final class NullScenarioAuthenticationService implements ScenarioAuthenticationService
{
    public function revokeUserSessions(int $userId): void
    {
    }
}
