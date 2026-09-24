<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Contract;

final class ScenarioAnalysisLifecycle
{
    public const DRAFT = 'draft';
    public const ACTIVE = 'active';
    public const MONITORED = 'monitored';
    public const CLOSED = 'closed';
    public const ARCHIVED = 'archived';

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, [
            self::DRAFT => [self::ACTIVE, self::ARCHIVED],
            self::ACTIVE => [self::MONITORED, self::CLOSED, self::ARCHIVED],
            self::MONITORED => [self::ACTIVE, self::CLOSED, self::ARCHIVED],
            self::CLOSED => [self::ARCHIVED],
            self::ARCHIVED => [],
        ][$from] ?? [], true);
    }
}
