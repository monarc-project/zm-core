<?php declare(strict_types=1);

namespace Unit\Entity;

use Monarc\Core\Entity\User;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class UserSuperClassTest extends TestCase
{
    /**
     * @covers \Monarc\Core\Entity\UserSuperClass::getPassword
     */
    public function testGetPasswordReturnsEmptyStringWhenPersistedValueIsNull(): void
    {
        $user = new User([
            'firstname' => 'Legacy',
            'lastname' => 'User',
            'email' => 'legacy@example.com',
            'language' => 1,
            'creator' => 'Tests',
            'role' => [],
        ]);

        $passwordProperty = new ReflectionProperty($user, 'password');
        $passwordProperty->setValue($user, null);

        self::assertSame('', $user->getPassword());
    }
}
