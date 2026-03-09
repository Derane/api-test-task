<?php

declare(strict_types=1);

namespace App\Tests\Unit\Factory;

use App\DTO\CreateUserDTO;
use App\Entity\User;
use App\Factory\UserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFactoryTest extends TestCase
{
    public function testCreateSetsAllFields(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed_password');

        $factory = new UserFactory($hasher);
        $dto = new CreateUserDTO(login: 'john', phone: '12345678', pass: 'secret');

        $user = $factory->create($dto);

        self::assertSame('john', $user->getLogin());
        self::assertSame('12345678', $user->getPhone());
        self::assertSame('hashed_password', $user->getPassword());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertNotNull($user->getApiToken());
        self::assertSame(64, strlen($user->getApiToken()));
    }

    public function testCreateGeneratesUniqueTokens(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed');

        $factory = new UserFactory($hasher);
        $dto = new CreateUserDTO(login: 'a', phone: '1', pass: 'p');

        $user1 = $factory->create($dto);
        $user2 = $factory->create($dto);

        self::assertNotSame($user1->getApiToken(), $user2->getApiToken());
    }
}
