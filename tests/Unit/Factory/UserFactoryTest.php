<?php

declare(strict_types=1);

namespace App\Tests\Unit\Factory;

use App\DTO\UserPayloadDTO;
use App\Entity\User;
use App\Factory\UserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFactoryTest extends TestCase
{
    private UserFactory $factory;

    protected function setUp(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed_password');

        $this->factory = new UserFactory($hasher);
    }

    public function testCreateSetsAllFields(): void
    {
        $dto = new UserPayloadDTO(login: 'john', phone: '12345678', pass: 'secret');

        $user = $this->factory->create($dto);

        self::assertSame('john', $user->getLogin());
        self::assertSame('12345678', $user->getPhone());
        self::assertSame('hashed_password', $user->getPassword());
        self::assertContains(User::ROLE_USER, $user->getRoles());
        self::assertNotNull($user->getApiToken());
        self::assertSame(64, strlen($user->getApiToken()));
    }

    public function testCreateGeneratesUniqueTokens(): void
    {
        $dto = new UserPayloadDTO(login: 'a', phone: '1', pass: 'p');

        $user1 = $this->factory->create($dto);
        $user2 = $this->factory->create($dto);

        self::assertNotSame($user1->getApiToken(), $user2->getApiToken());
    }

    public function testUpdateModifiesExistingUser(): void
    {
        $user = new User();
        $user->setLogin('old');
        $user->setPhone('00000000');
        $user->setPassword('old_hash');

        $dto = new UserPayloadDTO(login: 'new', phone: '11111111', pass: 'newpass');

        $result = $this->factory->update($user, $dto);

        self::assertSame($user, $result);
        self::assertSame('new', $user->getLogin());
        self::assertSame('11111111', $user->getPhone());
        self::assertSame('hashed_password', $user->getPassword());
    }
}
