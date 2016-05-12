<?php
namespace MonarcCore\Service;

class ConfigService extends AbstractService
{
    protected $config;


    public function getlanguage() {

        $language = $this->config['language'];
        $defaultLanguageIndex = $this->config['defaultLanguageIndex'];

        return [
            'language' => $language,
            'defaultLanguageIndex' => $defaultLanguageIndex,
        ];
    }
}