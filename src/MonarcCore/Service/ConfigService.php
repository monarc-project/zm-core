<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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
     * Get Language
     *
     * @return array
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
}