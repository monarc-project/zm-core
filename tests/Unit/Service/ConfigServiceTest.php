<?php declare(strict_types=1);

namespace Unit\Service;

use Monarc\Core\Service\ConfigService;
use PHPUnit\Framework\TestCase;

class ConfigServiceTest extends TestCase
{
    /**
     * @covers ConfigService::getLanguage
     * @covers ConfigService::getActiveLanguageCodes
     * @covers ConfigService::getUiLanguageCodes
     */
    public function testLanguageCatalogueDefinesDataLanguagesAndActiveLanguagesDefineUiLanguages(): void
    {
        $service = new ConfigService([
            'defaultLanguageIndex' => 1,
            'languages' => [
                'fr' => ['index' => 1, 'label' => 'Français'],
                'en' => ['index' => 2, 'label' => 'English'],
                'de' => ['index' => 3, 'label' => 'Deutsch'],
            ],
            'activeLanguages' => ['fr', 'en'],
        ]);

        self::assertSame([
            'languages' => [1 => 'Français', 2 => 'English', 3 => 'Deutsch'],
            'defaultLanguageIndex' => 1,
        ], $service->getLanguage());
        self::assertSame([1 => 'fr', 2 => 'en', 3 => 'de'], $service->getActiveLanguageCodes());
        self::assertSame(['fr', 'en'], $service->getUiLanguageCodes());
    }

    /**
     * @covers ConfigService::getHost
     * @dataProvider validPublicHostProvider
     */
    public function testGetHostReturnsConfiguredPublicHostWithoutTrailingSlash(
        string $publicHost,
        string $expected
    ): void {
        self::assertSame(
            $expected,
            (new ConfigService(['publicHost' => $publicHost]))->getHost()
        );
    }

    public static function validPublicHostProvider(): array
    {
        return [
            'https' => ['https://monarc.example.org', 'https://monarc.example.org'],
            'trailing slash' => ['https://monarc.example.org/', 'https://monarc.example.org'],
            'installation path' => ['https://monarc.example.org/client/', 'https://monarc.example.org/client'],
            'multiple trailing slashes' => [
                'https://monarc.example.org/client///',
                'https://monarc.example.org/client',
            ],
            'local FrontOffice' => ['http://localhost:5101', 'http://localhost:5101'],
            'local BackOffice' => ['http://localhost:5102', 'http://localhost:5102'],
            'local Vagrant' => ['http://127.0.0.1:5001', 'http://127.0.0.1:5001'],
            'uppercase scheme' => ['HTTPS://monarc.example.org', 'HTTPS://monarc.example.org'],
            'IPv6' => ['http://[::1]:5101/', 'http://[::1]:5101'],
        ];
    }

    /**
     * @covers ConfigService::getHost
     * @dataProvider invalidPublicHostProvider
     */
    public function testGetHostFailsClosedWhenPublicHostIsNotAnAbsoluteUrl($publicHost): void
    {
        $server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'attacker.example';
        $_SERVER['X_FORWARDED_HOST'] = 'attacker.example';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'attacker.example';
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('publicHost');
            (new ConfigService($publicHost === '__absent__' ? [] : ['publicHost' => $publicHost]))->getHost();
        } finally {
            $_SERVER = $server;
        }
    }

    public static function invalidPublicHostProvider(): array
    {
        return [
            'absent' => ['__absent__'],
            'empty' => [''],
            'slash only' => ['/'],
            'missing scheme' => ['monarc.example.org'],
            'scheme only' => ['https://'],
            'null' => [null],
            'boolean' => [true],
            'integer' => [5101],
            'array' => [['https://monarc.example.org']],
            'object' => [(object)['host' => 'https://monarc.example.org']],
            'unsupported scheme' => ['ftp://monarc.example.org'],
            'protocol relative' => ['//monarc.example.org'],
            'missing host with query' => ['https://?x=1'],
            'missing host with fragment' => ['https://#fragment'],
            'space in host' => ['https://exa mple.org'],
            'space in path' => ['https://monarc.example.org/client name'],
            'newline in path' => ["https://monarc.example.org/\nclient"],
            'invalid port' => ['https://monarc.example.org:bad'],
            'out of range port' => ['https://monarc.example.org:65536'],
            'username' => ['https://user@monarc.example.org'],
            'username and password' => ['https://user:password@monarc.example.org'],
            'at sign in path' => ['https://monarc.example.org/client@name'],
            'query string' => ['https://monarc.example.org?x=1'],
            'empty query string' => ['https://monarc.example.org/?'],
            'fragment' => ['https://monarc.example.org/#fragment'],
            'empty fragment' => ['https://monarc.example.org/#'],
        ];
    }

    /**
     * @covers ConfigService::getHost
     */
    public function testGetHostIgnoresForgedRequestHostHeader(): void
    {
        $server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'attacker.example';
        $_SERVER['X_FORWARDED_HOST'] = 'attacker.example';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'attacker.example';
        try {
            self::assertSame(
                'https://monarc.example.org',
                (new ConfigService(['publicHost' => 'https://monarc.example.org']))->getHost()
            );
        } finally {
            $_SERVER = $server;
        }
    }
}
