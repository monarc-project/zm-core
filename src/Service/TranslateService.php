<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Laminas\I18n\Translator\Translator;

class TranslateService
{
    protected Translator $translator;

    protected array $languages;

    public function __construct(Translator $translator, array $languages = [])
    {
        $this->translator = $translator;
        $this->languages = $languages;
    }

    /**
     * @param string $message The message to translate
     * @param null|int $languageIndex The language index or null for the default language
     *
     * @return string The translated message
     */
    public function translate(string $message, ?int $languageIndex = null): string
    {
        if ($languageIndex === null || !isset($this->languages[$languageIndex])) {
            return $this->translator->translate($message);
        }

        return $this->translator->translate($message, 'monarc', $this->languages[$languageIndex]);
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }
}
