<?php

declare(strict_types=1);

namespace App\Tests\Unit\Voter;

use App\Entity\User;
use App\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class UserVoterTest extends TestCase
{
    private UserVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new UserVoter();
    }

    private function createUser(int $id, array $roles): User
    {
        $user = new User();
        $user->setLogin('user' . $id);
        $user->setRoles($roles);

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, $id);

        return $user;
    }

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testRootCanViewAnyUser(): void
    {
        $root = $this->createUser(1, [User::ROLE_ROOT]);
        $target = $this->createUser(2, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($root), $target, [UserVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRootCanEditAnyUser(): void
    {
        $root = $this->createUser(1, [User::ROLE_ROOT]);
        $target = $this->createUser(2, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($root), $target, [UserVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRootCanCreate(): void
    {
        $root = $this->createUser(1, [User::ROLE_ROOT]);

        $result = $this->voter->vote($this->createToken($root), null, [UserVoter::CREATE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRootCanDeleteAnyUser(): void
    {
        $root = $this->createUser(1, [User::ROLE_ROOT]);
        $target = $this->createUser(2, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($root), $target, [UserVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCanViewSelf(): void
    {
        $user = $this->createUser(1, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($user), $user, [UserVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotViewOther(): void
    {
        $user = $this->createUser(1, [User::ROLE_USER]);
        $other = $this->createUser(2, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($user), $other, [UserVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCanEditSelf(): void
    {
        $user = $this->createUser(1, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($user), $user, [UserVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotEditOther(): void
    {
        $user = $this->createUser(1, [User::ROLE_USER]);
        $other = $this->createUser(2, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($user), $other, [UserVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCannotCreate(): void
    {
        $user = $this->createUser(1, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($user), null, [UserVoter::CREATE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCannotDelete(): void
    {
        $user = $this->createUser(1, [User::ROLE_USER]);

        $result = $this->voter->vote($this->createToken($user), $user, [UserVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUnauthenticatedUserDenied(): void
    {
        $target = $this->createUser(1, [User::ROLE_USER]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $target, [UserVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
