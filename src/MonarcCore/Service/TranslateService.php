<?php
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
     * Translator
     *
     * @param $message
     * @param null $langueIndex
     * @return mixed
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
     * @return null
     */
    public function getTranslator()
    {
        return $this->translator;
    }
}