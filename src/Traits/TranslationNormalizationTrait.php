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
     * @return array<string, string>
     */
    public function normalizeTranslations(mixed $translations): array
    {
        if (!is_array($translations)) {
            return [];
        }

        $normalizedTranslations = [];
        foreach ($translations as $languageCode => $value) {
            $trimmedValue = trim((string)$value);
            if ($trimmedValue !== '') {
                $normalizedTranslations[(string)$languageCode] = $trimmedValue;
            }
        }

        return $normalizedTranslations;
    }
}
