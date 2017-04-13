<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\I18n\Translator\Translator;

/**
 * Translate Service Factory
 *
 * Class TranslateServiceFactory
 * @package MonarcCore\Service
 */
class TranslateServiceFactory implements FactoryInterface
{
    /**
     * Create Service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return bool|TranslateService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $baseDir = '';
        if (is_dir('node_modules/ng_client/po')) {
            $baseDir = 'node_modules/ng_client/po/';
        } elseif (is_dir('node_modules/ng_backoffice/po')) {
            $baseDir = 'node_modules/ng_backoffice/po/';
        } else {
            return false;
        }
        $transConf = [
            'locale' => '',
            'translation_files' => [],
        ];

        $config = $serviceLocator->get('Config');
        $defaultLanguageIndex = $config['defaultLanguageIndex'];

        $confLanguages = $config['languages'];
        $languages = [];
        foreach ($confLanguages as $k => $l) {
            $languages[$l['index']] = $k;
            if (file_exists($baseDir . $k . '.mo')) {
                $transConf['translation_files'][] = [
                    'filename' => $baseDir . $k . '.mo',
                    'type' => 'gettext',
                    'text_domain' => 'monarc',
                    'locale' => $k,
                ];
            }
            if ($l['index'] == $defaultLanguageIndex) {
                $transConf['locale'] = $k;
            }
        }
        if (empty($transConf['translation_files'])) {
            $translator = null;
        } else {
            $translator = Translator::factory($transConf);
        }

        return new TranslateService($translator, $languages);
    }
}