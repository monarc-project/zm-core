<?php declare(strict_types=1);

namespace Unit\Service;

use DateTime;
use Laminas\Http\PhpEnvironment\Request;
use Monarc\Core\Entity\User;
use Monarc\Core\Entity\UserTokenSuperClass;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Table\UserTokenTable;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Monarc\Core\Service\ConnectedUserService
 */
class ConnectedUserServiceTest extends TestCase
{
    public function testReturnsTheConnectedUserForAnUnexpiredToken(): void
    {
        $request = new Request();
        $request->getHeaders()->addHeaderLine('token', 'active-token');
        $user = $this->createMock(User::class);
        $userToken = $this->createMock(UserTokenSuperClass::class);
        $userToken->method('getDateEnd')->willReturn(new DateTime('+1 minute'));
        $userToken->method('getUser')->willReturn($user);
        $userTokenTable = $this->createMock(UserTokenTable::class);
        $userTokenTable->expects($this->once())
            ->method('findByToken')
            ->with('active-token')
            ->willReturn($userToken);

        $service = new ConnectedUserService($request, $userTokenTable);

        self::assertSame($user, $service->getConnectedUser());
    }

    public function testDoesNotReturnAUserForAnExpiredToken(): void
    {
        $request = new Request();
        $request->getHeaders()->addHeaderLine('token', 'expired-token');
        $userToken = $this->createMock(UserTokenSuperClass::class);
        $userToken->method('getDateEnd')->willReturn(new DateTime('-1 minute'));
        $userTokenTable = $this->createMock(UserTokenTable::class);
        $userTokenTable->expects($this->once())
            ->method('findByToken')
            ->with('expired-token')
            ->willReturn($userToken);

        $service = new ConnectedUserService($request, $userTokenTable);

        self::assertNull($service->getConnectedUser());
    }
}
