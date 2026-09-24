<?php declare(strict_types=1);

namespace Unit\Service;

use Monarc\Core\Entity\User;
use Monarc\Core\Entity\UserTokenSuperClass;
use Monarc\Core\Scenario\Service\ScenarioAuthenticationService as ScenarioAuthenticationServiceContract;
use Monarc\Core\Service\AuthenticationService;
use Monarc\Core\Storage\Authentication as AuthenticationStorage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Monarc\Core\Service\AuthenticationService
 */
class AuthenticationServiceTest extends TestCase
{
    public function testLogoutRevokesScenarioSessionsAfterRemovingTheLegacyToken(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $user->method('getId')->willReturn(42);
        $userToken = $this->createMock(UserTokenSuperClass::class);
        $userToken->method('getUser')->willReturn($user);

        $authenticationStorage = $this->createMock(AuthenticationStorage::class);
        $authenticationStorage->expects($this->once())
            ->method('getUserToken')
            ->with('legacy-token')
            ->willReturn($userToken);
        $authenticationStorage->expects($this->once())
            ->method('removeUserToken')
            ->with('legacy-token');

        $scenarioAuthenticationService = $this->createMock(ScenarioAuthenticationServiceContract::class);
        $scenarioAuthenticationService->expects($this->once())
            ->method('revokeUserSessions')
            ->with(42);

        $service = (new \ReflectionClass(AuthenticationService::class))->newInstanceWithoutConstructor();
        $authenticationStorageProperty = new \ReflectionProperty(
            AuthenticationService::class,
            'authenticationStorage'
        );
        $authenticationStorageProperty->setValue($service, $authenticationStorage);
        $scenarioServiceProperty = new \ReflectionProperty(
            AuthenticationService::class,
            'scenarioAuthenticationService'
        );
        $scenarioServiceProperty->setValue($service, $scenarioAuthenticationService);

        self::assertTrue($service->logout(['token' => 'legacy-token']));
    }
}
