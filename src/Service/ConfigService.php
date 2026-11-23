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
        $l = [];
        foreach ($languages as $language) {
            $l[$language['index']] = $language['label'];
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
                $this->languageCodes[$languageData['index']] = $languageCode;
            }
        }

        return $this->languageCodes;
    }

    public function getDefaultLanguageCode(): string
    {
        return $this->getLanguageCodes()[$this->config['defaultLanguageIndex'] ?? 1] ?? 'fr';
    }

    public function getActiveLanguageCodes(): array
    {
        return $this->getLanguageCodes();
    }

    public function getUiLanguageCodes(): array
    {
        return array_values(array_unique(
            $this->config['activeLanguages'] ?? array_values($this->getLanguageCodes())
        ));
    }

    /**
     * Returns the configured public base URL (including any installation path), without a trailing slash.
     *
     * The value must come from server-side configuration only. Deriving it from the request
     * Host header (HTTP_HOST / X-Forwarded-Host) would let an unauthenticated attacker poison
     * absolute URLs such as the password reset link, so this method fails closed instead.
     *
     * @throws \RuntimeException when publicHost is not configured as an absolute http(s) URL.
     */
    public function getHost(): string
    {
        $host = $this->config['publicHost'] ?? '';
        if (!is_string($host)
            || filter_var($host, FILTER_VALIDATE_URL) === false
            || !preg_match('#^https?://#i', $host)
            || strpbrk($host, '@?#') !== false
        ) {
            throw new \RuntimeException(
                'The "publicHost" configuration option must be an absolute http(s) base URL'
                . ' without credentials, a query string or a fragment'
                . ' (e.g. https://monarc.example.org/client).'
            );
        }

        return rtrim($host, '/');
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
