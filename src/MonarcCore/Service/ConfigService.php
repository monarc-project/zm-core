<?php
namespace MonarcCore\Service;

class ConfigService extends AbstractService
{
    protected $config;


    public function getlanguage() {

        $languages = $this->config['languages'];
        $defaultLanguageIndex = $this->config['defaultLanguageIndex'];

        $activeLanguages = isset($this->config['activeLanguages'])?$this->config['activeLanguages']:array();

        $l = array();
        if(empty($activeLanguages)){
            foreach($languages as $k => $v){
                $l[$v['index']] = $v['label'];
            }
        }else{
            foreach($activeLanguages as $k){
                if(isset($languages[$k])){
                    $l[$languages[$k]['index']] = $languages[$k]['label'];
                }
            }
        }
        return [
            'languages' => $l,
            'defaultLanguageIndex' => $defaultLanguageIndex,
        ];
    }
}