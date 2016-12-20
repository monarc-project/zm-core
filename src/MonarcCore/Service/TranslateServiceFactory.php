<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\I18n\Translator\Translator;

class TranslateServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator){
        $baseDir = '';
        if(is_dir('node_modules/ng_client/po')){
            $baseDir = 'node_modules/ng_client/po/';
        }elseif(is_dir('node_modules/ng_backoffice/po')){
            $baseDir = 'node_modules/ng_backoffice/po/';
        }else{
            return false;
        }
        $transConf = [
            'local' => '',
            'translation_files' => [],
        ];

        $config = $serviceLocator->get('Config');
        $defaultLanguageIndex = $config['defaultLanguageIndex'];

        $confLanguages = $config['languages'];
        $languages = [];
        foreach($confLanguages as $k => $l){
            $languages[$l['index']] = $k;
            if(file_exists($baseDir.$k.'.mo')){
                $transConf['translation_files'][] = [
                    'filename' => $baseDir.$k.'.mo',
                    'type' => 'gettext',
                    'text_domain' => 'monarc',
                    'local' => $k,
                ];
            }
            if($l['index'] == $defaultLanguageIndex){
                $transConf['local'] = $k;
            }
        }
        if(empty($transConf['translation_files'])){
            $translator = null;
        }else{
            $translator = Translator::factory($transConf);
        }

        return new TranslateService($translator, $languages);
    }
}