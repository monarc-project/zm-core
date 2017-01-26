<?php
namespace MonarcCore\Service;

class TranslateService
{
    protected $translator = null;
    protected $languages = [];

    public function __construct($translator = null, $languages = [])
    {
        $this->translator = $translator;
        $this->languages = $languages;
    }

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

    public function getTranslator()
    {
        return $this->translator;
    }
}