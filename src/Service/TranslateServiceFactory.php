<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\I18n\Translator\Translator;

class TranslateServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $projectRoot = \defined('PROJECT_ROOT') ? PROJECT_ROOT : '';
        $baseDir = $projectRoot . 'node_modules/ng_client/po/';
        if (is_dir($projectRoot . 'node_modules/ng_backoffice/po')) {
            $baseDir = $projectRoot . 'node_modules/ng_backoffice/po/';
        }
        $translationConfig = [
            'locale' => '',
            'translation_files' => [],
        ];

        $config = $container->get('Config');
        $defaultLanguageIndex = $config['defaultLanguageIndex'];

        $confLanguages = $config['languages'];
        $languages = [];
        foreach ($confLanguages as $key => $language) {
            $languages[$language['index']] = $key;
            if (file_exists($baseDir . $key . '.mo')) {
                $translationConfig['translation_files'][] = [
                    'filename' => $baseDir . $key . '.mo',
                    'type' => 'gettext',
                    'text_domain' => 'monarc',
                    'locale' => $key,
                ];
            }
            if ($language['index'] === $defaultLanguageIndex) {
                $translationConfig['locale'] = $key;
            }
        }

        $translator = null;
        if (!empty($translationConfig['translation_files'])) {
            $translator = Translator::factory($translationConfig);
        }

        return new TranslateService($translator, $languages);
    }
}
