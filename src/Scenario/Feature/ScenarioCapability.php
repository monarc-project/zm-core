<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Feature;

use Monarc\Core\Exception\ActionForbiddenException;

final class ScenarioCapability
{
    public function __construct(private array $config)
    {
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * Keeps Scenario resource controllers fail-closed while allowing their
     * health and readiness probes to report the deployment state separately.
     */
    public function assertEnabled(): void
    {
        if (!$this->isEnabled()) {
            throw new ActionForbiddenException('Scenario access is unavailable.');
        }
    }

    /**
     * The identity setting is deliberately server-side and constrained to the
     * two approved paths.  Consumers construct this capability from final,
     * merged application configuration, so an invalid deployment setting fails
     * before a Scenario controller can select an authentication path.
     */
    public function identityMode(): string
    {
        $mode = $this->config['identity']['mode'] ?? 'legacy';
        if (!\is_string($mode) || !\in_array($mode, ['legacy', 'zitadel'], true)) {
            throw new \InvalidArgumentException('scenario.identity.mode must be legacy or zitadel');
        }

        return $mode;
    }
}
