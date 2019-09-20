<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\I18n\Translator\Translator;

/**
 * Translate Service Factory
 *
 * Class TranslateServiceFactory
 * @package Monarc\Core\Service
 */
class TranslateServiceFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
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

        $config = $container->get('Config');
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

        $translator = null;
        if (!empty($transConf['translation_files'])) {
            $translator = Translator::factory($transConf);
        }

        return new TranslateService($translator, $languages);
    }
}
