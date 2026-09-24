<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Service;

/**
 * Optional Scenario-session actions invoked by the shared authentication flow.
 */
interface ScenarioAuthenticationService
{
    public function revokeUserSessions(int $userId): void;
}
