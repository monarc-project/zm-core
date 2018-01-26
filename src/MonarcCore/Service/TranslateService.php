<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Translate Service
 *
 * Class TranslateService
 * @package MonarcCore\Service
 */
class TranslateService
{
    protected $translator = null;
    protected $languages = [];

    /**
     * TranslateService constructor.
     * @param null $translator
     * @param array $languages
     */
    public function __construct($translator = null, $languages = [])
    {
        $this->translator = $translator;
        $this->languages = $languages;
    }

    /**
     * Translates the provided message into the target language in $langueIndex
     * @param string $message The message to translate
     * @param null|int $langueIndex The language index or null for the default language
     * @return string The translated message
     */
    public function translate($message, $langueIndex = null)
    {
        if (!$this->translator) {
            return $message;
        } elseif (empty($langueIndex) || !isset($this->languages[$langueIndex])) {
            return $this->translator->translate($message);
        } else {
            return $this->translator->translate($message, 'monarc', $this->languages[$langueIndex]);
        }
    }

    /**
     * @return Translator The translator service
     */
    public function getTranslator()
    {
        return $this->translator;
    }
}