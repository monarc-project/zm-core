<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Traits;

trait TranslationNormalizationTrait
{
    /**
     * @param string[] $supportedLanguageCodes An empty list accepts every language code.
     * @return array<string, string>
     */
    public function normalizeTranslations(
        mixed $translations,
        array $supportedLanguageCodes = [],
        mixed $fallbackValue = null,
        ?string $fallbackLanguageCode = null
    ): array
    {
        if (!is_array($translations)) {
            $translations = [];
        }

        $normalizedTranslations = [];
        foreach ($translations as $languageCode => $value) {
            $trimmedValue = trim((string)$value);
            if ($trimmedValue !== ''
                && ($supportedLanguageCodes === [] || in_array($languageCode, $supportedLanguageCodes, true))
            ) {
                $normalizedTranslations[(string)$languageCode] = $trimmedValue;
            }
        }

        $fallbackValue = trim((string)$fallbackValue);
        if ($normalizedTranslations === []
            && $fallbackValue !== ''
            && $fallbackLanguageCode !== null
            && ($supportedLanguageCodes === [] || in_array($fallbackLanguageCode, $supportedLanguageCodes, true))
        ) {
            $normalizedTranslations[$fallbackLanguageCode] = $fallbackValue;
        }

        return $normalizedTranslations;
    }

    public function normalizeTranslationValue(mixed $value): string
    {
        return trim((string)$value);
    }
}
