<?php
namespace MonarcCore\Service;

class ConfigService extends AbstractService
{
    protected $config;


    public function getlanguage() {

        $languages = $this->config['languages'];
        $defaultLanguageIndex = $this->config['defaultLanguageIndex'];

        return [
            'languages' => $languages,
            'defaultLanguageIndex' => $defaultLanguageIndex,
        ];
    }
}