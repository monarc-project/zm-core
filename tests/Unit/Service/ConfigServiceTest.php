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
     */
    public function testGetHostReturnsConfiguredPublicHostWithoutTrailingSlash(): void
    {
        self::assertSame(
            'https://monarc.example.org',
            (new ConfigService(['publicHost' => 'https://monarc.example.org/']))->getHost()
        );
    }

    /**
     * @covers ConfigService::getHost
     * @dataProvider invalidPublicHostProvider
     */
    public function testGetHostFailsClosedWhenPublicHostIsNotAnAbsoluteUrl($publicHost): void
    {
        $this->expectException(\RuntimeException::class);
        (new ConfigService($publicHost === '__absent__' ? [] : ['publicHost' => $publicHost]))->getHost();
    }

    public function invalidPublicHostProvider(): array
    {
        return [
            'absent' => ['__absent__'],
            'empty' => [''],
            'slash only' => ['/'],
            'missing scheme' => ['monarc.example.org'],
            'scheme only' => ['https://'],
        ];
    }

    /**
     * @covers ConfigService::getHost
     */
    public function testGetHostIgnoresForgedRequestHostHeader(): void
    {
        $_SERVER['HTTP_HOST'] = 'attacker.example';
        $_SERVER['X_FORWARDED_HOST'] = 'attacker.example';
        try {
            self::assertSame(
                'https://monarc.example.org',
                (new ConfigService(['publicHost' => 'https://monarc.example.org']))->getHost()
            );
        } finally {
            unset($_SERVER['HTTP_HOST'], $_SERVER['X_FORWARDED_HOST']);
        }
    }
}
