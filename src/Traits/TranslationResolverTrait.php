<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Traits;

trait TranslationResolverTrait
{
    /**
     * @param array<string, string> $translations
     */
    protected function resolveTranslation(
        array $translations,
        string $fallbackValue,
        ?string $languageCode = null
    ): string {
        $languageCode ??= $this->getCurrentLanguageCode();
        foreach ([$languageCode, $this->configService->getDefaultLanguageCode()] as $langCode) {
            if (isset($translations[$langCode]) && $translations[$langCode] !== '') {
                return $translations[$langCode];
            }
        }

        foreach ($translations as $translation) {
            if ($translation !== '') {
                return $translation;
            }
        }

        return $fallbackValue;
    }

    /**
     * @param array<string, string> $translations
     * @return array<string, string>
     */
    protected function resolveTranslations(array $translations, string $fallbackValue): array
    {
        $localizedTranslations = [];
        foreach ($this->getSupportedLanguageCodes() as $languageCode) {
            $localizedTranslations[$languageCode] = $this->resolveTranslation(
                $translations,
                $fallbackValue,
                $languageCode
            );
        }

        return $localizedTranslations;
    }

    protected function getCurrentLanguageCode(): string
    {
        return $this->configService->getLanguageCodes()[$this->connectedUser->getLanguage()]
            ?? $this->configService->getDefaultLanguageCode();
    }

    /**
     * @return string[]
     */
    protected function getSupportedLanguageCodes(): array
    {
        $languageCodes = $this->configService->getActiveLanguageCodes();

        return array_values($languageCodes !== [] ? $languageCodes : $this->configService->getLanguageCodes());
    }
}
