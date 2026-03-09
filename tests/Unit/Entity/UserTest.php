<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        $user = new User();
        self::assertContains(User::ROLE_USER, $user->getRoles());
    }

    public function testGetRolesIncludesAssignedRoles(): void
    {
        $user = new User();
        $user->setRoles([User::ROLE_ROOT]);

        self::assertContains(User::ROLE_ROOT, $user->getRoles());
        self::assertContains(User::ROLE_USER, $user->getRoles());
    }

    public function testHasRole(): void
    {
        $user = new User();
        $user->setRoles([User::ROLE_ROOT]);

        self::assertTrue($user->hasRole(User::ROLE_ROOT));
        self::assertTrue($user->hasRole(User::ROLE_USER));
        self::assertFalse($user->hasRole('ROLE_ADMIN'));
    }

    public function testGetUserIdentifierReturnsLogin(): void
    {
        $user = new User();
        $user->setLogin('mylogin');

        self::assertSame('mylogin', $user->getUserIdentifier());
    }

    public function testSettersAndGetters(): void
    {
        $user = new User();
        $user->setLogin('test');
        $user->setPhone('12345678');
        $user->setPassword('hashed');
        $user->setApiToken('token123');

        self::assertSame('test', $user->getLogin());
        self::assertSame('12345678', $user->getPhone());
        self::assertSame('hashed', $user->getPassword());
        self::assertSame('token123', $user->getApiToken());
        self::assertNull($user->getId());
    }
}
