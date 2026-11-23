<?php declare(strict_types=1);

namespace Unit\Service;

use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Service\MailService;
use Monarc\Core\Service\PasswordService;
use Monarc\Core\Table\PasswordTokenTable;
use Monarc\Core\Table\UserTable;
use PHPUnit\Framework\TestCase;

class PasswordServiceTest extends TestCase
{
    /**
     * @covers PasswordService::passwordForgotten
     */
    public function testPasswordForgottenDoesNotPersistTokenWhenPublicHostIsNotConfigured(): void
    {
        $userTable = $this->createMock(UserTable::class);
        $userTable->expects(self::once())
            ->method('findByEmail')
            ->with('user@example.org')
            ->willReturn($this->createMock(UserSuperClass::class));
        $passwordTokenTable = $this->createMock(PasswordTokenTable::class);
        $passwordTokenTable->expects(self::never())->method('save');
        $mailService = $this->createMock(MailService::class);
        $mailService->expects(self::never())->method('send');

        $service = new PasswordService($passwordTokenTable, $userTable, $mailService, new ConfigService([]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('publicHost');
        $service->passwordForgotten('user@example.org');
    }
}
