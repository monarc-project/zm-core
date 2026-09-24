<?php declare(strict_types=1);

namespace Monarc\Core\Scenario\Contract;

final class AnalysisType
{
    public const ASSET = 'asset';
    public const SCENARIO = 'scenario';

    /** @return string[] */
    public static function all(): array
    {
        return [self::ASSET, self::SCENARIO];
    }

    public static function isValid(string $analysisType): bool
    {
        return \in_array($analysisType, self::all(), true);
    }
}
