<?php declare(strict_types=1);

namespace Unit\Storage;

use Monarc\Core\Entity\User;
use Monarc\Core\Entity\UserToken;
use Monarc\Core\Entity\UserTokenSuperClass;
use Monarc\Core\Storage\Authentication;
use Monarc\Core\Table\UserTokenTable;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Monarc\Core\Storage\Authentication
 */
class AuthenticationTest extends TestCase
{
    public function testCreatesTokenExpiryInUtc(): void
    {
        $user = $this->createMock(User::class);
        $user->method('addUserToken')->willReturnSelf();

        $table = $this->createMock(UserTokenTable::class);
        $table->method('findByUser')->with($user)->willReturn([]);
        $table->expects($this->once())->method('flush');
        $table->method('findByToken')->with('token')->willReturn(null);
        $table->method('getEntityName')->willReturn(UserToken::class);
        $table->expects($this->once())
            ->method('save')
            ->with($this->callback(static function (UserTokenSuperClass $token): bool {
                self::assertSame('UTC', $token->getDateEnd()->getTimezone()->getName());

                return true;
            }));

        $storage = new Authentication($table, ['monarc' => ['ttl' => 60]]);

        self::assertTrue($storage->addUserToken('token', $user));
    }
}
