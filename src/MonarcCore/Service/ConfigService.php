<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Config Service
 *
 * Class ConfigService
 * @package MonarcCore\Service
 */
class ConfigService extends AbstractService
{
    protected $config;

    /**
     * Retrieves the languages from the configuration
     * @return array The configuration's languages
     */
    public function getlanguage()
    {
        $languages = $this->config['languages'];
        $defaultLanguageIndex = $this->config['defaultLanguageIndex'];

        $activeLanguages = isset($this->config['activeLanguages']) ? $this->config['activeLanguages'] : [];

        $l = [];
        if (empty($activeLanguages)) {
            foreach ($languages as $k => $v) {
                $l[$v['index']] = $v['label'];
            }
        } else {
            foreach ($activeLanguages as $k) {
                if (isset($languages[$k])) {
                    $l[$languages[$k]['index']] = $languages[$k]['label'];
                }
            }
        }
        return [
            'languages' => $l,
            'defaultLanguageIndex' => $defaultLanguageIndex,
        ];
    }

    public function gethost()
    {
        return isset($this->config['publicHost']) ? $this->config['publicHost'] : '';
    }

    public function getAppVersion()
    {
        return [
            'appVersion' => isset($this->config['appVersion']) ? $this->config['appVersion'] : '',
        ];
    }

    public function getCheckVersion()
    {
        return [
            'checkVersion' => isset($this->config['checkVersion']) ? $this->config['checkVersion'] : true,
        ];
    }

    public function getAppCheckingURL()
    {
        return [
            'appCheckingURL' => isset($this->config['appCheckingURL']) ? $this->config['appCheckingURL'] : 'https://version.monarc.lu/check/MONARC',
        ];
    }

    public function getemail()
    {
        return [
            'from' =>
                isset($this->config['email']['from']) ? $this->config['email']['from'] : 'info@monarc.lu',
            'name' =>
                isset($this->config['email']['name']) ? $this->config['email']['name'] : 'MONARC',
        ];
    }
}
