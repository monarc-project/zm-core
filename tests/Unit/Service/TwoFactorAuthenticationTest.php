<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Service;

use Composer\InstalledVersions;
use Laminas\Authentication\Result;
use Laminas\View\Model\JsonModel;
use Monarc\Core\Adapter\Authentication;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Service\ActionHistoryService;
use Monarc\Core\Service\AuthenticationService;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Storage\Authentication as AuthenticationStorage;
use Monarc\Core\Table\UserTable;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RobThree\Auth\Algorithm;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

/** @coversNothing */
class TwoFactorAuthenticationTest extends TestCase
{
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testColdAuthenticationSetupReturnsLocalPngWithoutIssuingLoginToken(): void
    {
        $adapter = $this->createMock(Authentication::class);
        $adapter->method('setIdentity')->willReturnSelf();
        $adapter->method('setCredential')->willReturnSelf();
        $adapter->method('authenticate')->willReturn(new Result(Authentication::TWO_FA_TO_SET_UP, 'user'));
        $adapter->method('getUser')->willReturn($this->createMock(UserSuperClass::class));
        $storage = $this->createMock(AuthenticationStorage::class);
        $storage->expects(self::never())->method('addUserToken');
        $service = new AuthenticationService($this->createMock(ConfigService::class), $storage, $adapter);

        $result = $service->authenticate(['login' => 'user@example.invalid', 'password' => 'test password']);

        self::assertSame(AuthenticationService::TWO_FA_CODE_TO_BE_CONFIGURED, $result['token']);
        self::assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $result['secret']);
        $this->assertPng($result['qrcode']);
    }

    /** @dataProvider knownCodes */
    public function testExistingSecretsRetainSixDigitSha1Codes(int $time, string $code, string $secret): void
    {
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider(), 'MONARC', 6, 30, Algorithm::Sha1);

        self::assertSame($code, $tfa->getCode($secret, $time));
        self::assertTrue($tfa->verifyCode($secret, $code, 0, $time));
        self::assertFalse($tfa->verifyCode($secret, $code, 0, $time + 120));
        self::assertFalse($tfa->verifyCode($secret, '00000', 1, $time));
        $uri = $tfa->getQRText('MONARC', $secret);
        parse_str(parse_url($uri, PHP_URL_QUERY), $query);
        self::assertSame($secret, $query['secret']);
        self::assertSame('MONARC', $query['issuer']);
        self::assertSame('SHA1', $query['algorithm']);
        self::assertSame('6', $query['digits']);
        self::assertSame('30', $query['period']);
    }

    public static function knownCodes(): array
    {
        // RFC 6238 SHA-1 vectors, reduced to MONARC's existing six-digit setting.
        return [
            [59, '287082', self::SECRET],
            [1111111109, '081804', self::SECRET],
            [59, '996554', 'JBSWY3DPEHPK3PXP'],
        ];
    }

    public function testOfficeProfileSetupReturnsPngWithoutSavingUser(): void
    {
        $user = $this->officeUser();
        $user->method('getId')->willReturn(42);
        $table = $this->createMock(UserTable::class);
        $table->expects(self::never())->method('save');
        $result = $this->responseData($this->officeController($user, $table)->get(42));

        self::assertSame(42, $result['id']);
        self::assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $result['secret']);
        $this->assertPng($result['qrcode']);
    }

    /** @dataProvider validAndInvalidCodes */
    public function testOfficeActivationOnlyPersistsVerifiedSecret(bool $valid): void
    {
        $user = $this->officeUser();
        $user->expects($valid ? self::once() : self::never())->method('setSecretKey')->with(self::SECRET);
        $user->expects($valid ? self::once() : self::never())->method('setTwoFactorAuthEnabled')->with(true);
        $table = $this->createMock(UserTable::class);
        $table->expects($valid ? self::once() : self::never())->method('save')->with($user);
        $controller = $this->officeController($user, $table);
        $code = $valid ? (new TwoFactorAuth(new EndroidQrCodeProvider(), 'MONARC'))->getCode(self::SECRET) : '00000';

        $result = $this->responseData($controller->create(['secretKey' => self::SECRET, 'verificationCode' => $code]));

        self::assertSame($valid, $result['status']);
    }

    /** @dataProvider validAndInvalidCodes */
    public function testExistingTwoFactorLoginAcceptsOnlyVerifiedCodes(bool $valid): void
    {
        $user = $this->createMock(UserSuperClass::class);
        $user->method('isActive')->willReturn(true);
        $user->method('getPassword')->willReturn(password_hash('test password', PASSWORD_DEFAULT));
        $user->method('isTwoFactorAuthEnabled')->willReturn(true);
        $user->method('getSecretKey')->willReturn(self::SECRET);
        $user->method('getRecoveryCodes')->willReturn([]);
        $table = $this->createMock(UserTable::class);
        $table->method('findByEmail')->willReturn($user);
        $table->expects(self::never())->method('save');
        $adapter = new Authentication(
            $table,
            $this->createMock(ConfigService::class),
            $this->createMock(ActionHistoryService::class)
        );
        $adapter->setIdentity('user@example.invalid')->setCredential('test password');
        $code = $valid ? (new TwoFactorAuth(new EndroidQrCodeProvider(), 'MONARC'))->getCode(self::SECRET) : '00000';

        $result = $adapter->authenticate($code);

        self::assertSame($valid ? Result::SUCCESS : Authentication::TWO_FA_FAILED, $result->getCode());
    }

    public static function validAndInvalidCodes(): array
    {
        return [[true], [false]];
    }

    public function testRecoveryCodeIsConsumedOnceAndOtherCodesAreRetained(): void
    {
        $retained = password_hash('another recovery code', PASSWORD_DEFAULT);
        $user = $this->createMock(UserSuperClass::class);
        $user->method('isActive')->willReturn(true);
        $user->method('getPassword')->willReturn(password_hash('test password', PASSWORD_DEFAULT));
        $user->method('isTwoFactorAuthEnabled')->willReturn(true);
        $user->method('getSecretKey')->willReturn(self::SECRET);
        $user->method('getRecoveryCodes')->willReturn([
            password_hash('used recovery code', PASSWORD_DEFAULT), $retained,
        ]);
        $user->expects(self::once())->method('setRecoveryCodes')->with([1 => $retained]);
        $table = $this->createMock(UserTable::class);
        $table->method('findByEmail')->willReturn($user);
        $table->expects(self::once())->method('save')->with($user);
        $adapter = new Authentication(
            $table,
            $this->createMock(ConfigService::class),
            $this->createMock(ActionHistoryService::class)
        );
        $adapter->setIdentity('user@example.invalid')->setCredential('test password');

        self::assertSame(Result::SUCCESS, $adapter->authenticate('used recovery code')->getCode());
    }

    private function officeUser(): UserSuperClass
    {
        return $this->createMock(InstalledVersions::isInstalled('monarc/frontoffice')
            ? \Monarc\FrontOffice\Entity\User::class : \Monarc\Core\Entity\User::class);
    }

    private function officeController(UserSuperClass $user, UserTable $table): object
    {
        $identity = $this->createMock(ConnectedUserService::class);
        $identity->method('getConnectedUser')->willReturn($user);
        $config = $this->createMock(ConfigService::class);
        if (InstalledVersions::isInstalled('monarc/frontoffice')) {
            return new \Monarc\FrontOffice\Controller\ApiUserTwoFAController($config, $table, $identity);
        }

        return new \Monarc\BackOffice\Controller\ApiUserTwoFAController($config, $identity, $table);
    }

    private function responseData(ResponseInterface|JsonModel $response): array
    {
        return $response instanceof JsonModel ? $response->getVariables()
            : json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function assertPng(string $uri): void
    {
        self::assertStringStartsWith('data:image/png;base64,', $uri);
        $image = base64_decode(substr($uri, strlen('data:image/png;base64,')), true);
        self::assertSame("\x89PNG\r\n\x1a\n", substr($image, 0, 8));
        $size = getimagesizefromstring($image);
        self::assertSame([200, 200], [$size[0], $size[1]]);
    }
}
