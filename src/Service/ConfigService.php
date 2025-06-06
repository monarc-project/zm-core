<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Config Service
 *
 * Class ConfigService
 * @package Monarc\Core\Service
 */
class ConfigService
{
    /** @var array */
    protected $config;

    private array $languageCodes = [];

    private array $activeLanguageCodes = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getConfigOption(string $optionName, $default = null)
    {
        return $this->config[$optionName] ?? $default;
    }

    public function getLanguage(): array
    {
        $languages = $this->config['languages'];
        $defaultLanguageIndex = $this->config['defaultLanguageIndex'];

        $activeLanguages = $this->config['activeLanguages'] ?? [];

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

    public function getLanguageCodes(): array
    {
        if (empty($this->languageCodes)) {
            foreach ($this->config['languages'] as $languageCode => $languageData) {
                $this->languageCodes[$languageData['index']] = strtoupper($languageCode);
            }
        }

        return $this->languageCodes;
    }

    public function getActiveLanguageCodes(): array
    {
        if (empty($this->activeLanguageCodes)) {
            $activeLanguages = $this->config['activeLanguages'] ?? [];
            foreach ($this->config['languages'] as $languageCode => $languageData) {
                if (\in_array($languageCode, $activeLanguages, true)) {
                    $this->activeLanguageCodes[$languageData['index']] = $languageCode;
                }
            }
        }

        return $this->activeLanguageCodes;
    }

    public function getHost(): string
    {
        if (!empty($this->config['publicHost'])) {
            return $this->config['publicHost'];
        }

        // Determine HTTP/HTTPS proto, and HTTP_HOST
        if (isset($_SERVER['X_FORWARDED_PROTO'])) {
            $proto = strtolower($_SERVER['X_FORWARDED_PROTO']);
        } elseif (isset($_SERVER['X_URL_SCHEME'])) {
            $proto = strtolower($_SERVER['X_URL_SCHEME']);
        } elseif (isset($_SERVER['X_FORWARDED_SSL'])) {
            $proto = strtolower($_SERVER['X_FORWARDED_SSL']) === 'on' ? 'https' : 'http';
        } elseif (isset($_SERVER['FRONT_END_HTTPS'])) { // Microsoft variant
            $proto = strtolower($_SERVER['FRONT_END_HTTPS']) === 'on' ? 'https' : 'http';
        } elseif (isset($_SERVER['HTTPS'])) {
            $proto = 'https';
        } else {
            $proto = 'http';
        }

        if (isset($_SERVER['X_FORWARDED_HOST'])) {
            return $proto. '://' . $_SERVER['X_FORWARDED_HOST'];
        }

        return $proto. '://' . $_SERVER['HTTP_HOST'];
    }

    public function getAppVersion(): array
    {
        return [
            'appVersion' => $this->config['appVersion'] ?? '',
        ];
    }

    public function getCheckVersion(): array
    {
        return [
            'checkVersion' => $this->config['checkVersion'] ?? true,
        ];
    }

    public function getAppCheckingURL(): array
    {
        return [
            'appCheckingURL' => $this->config['appCheckingURL'] ?? 'https://version.monarc.lu/check/MONARC',
        ];
    }

    public function getInstanceName(): string
    {
        return $this->config['instanceName'] ?? '';
    }

    public function getMospApiUrl(): array
    {
        return [
            'mospApiUrl' => $this->config['mospApiUrl'] ?? 'https://objects.monarc.lu/api/v1/',
        ];
    }

    public function getTerms(): array
    {
        return [
            'terms' => $this->config['terms'] ?? '',
        ];
    }

    public function getEmail(): array
    {
        return [
            'from' => $this->config['email']['from'] ?? 'info@monarc.lu',
            'name' => $this->config['email']['name'] ?? 'Monarc',
        ];
    }

    public function isTwoFactorAuthEnforced(): bool
    {
        return $this->config['twoFactorAuthEnforced'] ?? false;
    }

    public function getCaptchaConfig(): array
    {
        return $this->config['captcha'] ?? ['enabled' => false];
    }
}
