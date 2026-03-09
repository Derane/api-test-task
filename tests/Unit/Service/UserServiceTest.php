<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\UserPayloadDTO;
use App\Entity\User;
use App\Exception\UserNotFoundException;
use App\Factory\UserFactoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserRepositoryInterface&MockObject $repository;
    private UserFactoryInterface&MockObject $factory;
    private UserService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->factory = $this->createMock(UserFactoryInterface::class);

        $this->service = new UserService(
            $this->em,
            $this->repository,
            $this->factory,
        );
    }

    private function createUser(int $id, string $login = 'test', string $phone = '12345678', array $roles = []): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setPhone($phone);
        $user->setRoles($roles);

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, $id);

        return $user;
    }

    public function testListAsRootReturnsAllUsers(): void
    {
        $root = $this->createUser(1, 'root', '00000000', [User::ROLE_ROOT]);
        $user2 = $this->createUser(2, 'bob', '22222222');

        $this->repository->method('findAll')->willReturn([$root, $user2]);

        $result = $this->service->list($root);

        self::assertCount(2, $result);
        self::assertSame('root', $result[0]->login);
        self::assertSame('bob', $result[1]->login);
    }

    public function testListAsUserReturnsOnlySelf(): void
    {
        $user = $this->createUser(1, 'alice', '11111111', [User::ROLE_USER]);

        $result = $this->service->list($user);

        self::assertCount(1, $result);
        self::assertSame('alice', $result[0]->login);
    }

    public function testFindOrFailReturnsUser(): void
    {
        $user = $this->createUser(1);
        $this->repository->method('findById')->with(1)->willReturn($user);

        $result = $this->service->findOrFail(1);

        self::assertSame($user, $result);
    }

    public function testFindOrFailThrowsWhenNotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User with id 999 not found.');

        $this->service->findOrFail(999);
    }

    public function testCreatePersistsAndReturnsDTO(): void
    {
        $dto = new UserPayloadDTO(login: 'new', phone: '99999999', pass: 'pass');
        $user = $this->createUser(1, 'new', '99999999');

        $this->factory->method('create')->with($dto)->willReturn($user);
        $this->em->expects(self::once())->method('persist')->with($user);
        $this->em->expects(self::once())->method('flush');

        $result = $this->service->create($dto);

        self::assertSame('new', $result->login);
        self::assertSame('99999999', $result->phone);
    }

    public function testUpdateDelegatesToFactoryAndFlushes(): void
    {
        $user = $this->createUser(1, 'old', '00000000');
        $dto = new UserPayloadDTO(login: 'updated', phone: '11111111', pass: 'newpass');

        $this->factory->expects(self::once())->method('update')->with($user, $dto)->willReturn($user);
        $this->em->expects(self::once())->method('flush');

        $result = $this->service->update($user, $dto);

        self::assertSame(1, $result->id);
    }

    public function testDeleteRemovesUserAndFlushes(): void
    {
        $user = $this->createUser(1);

        $this->em->expects(self::once())->method('remove')->with($user);
        $this->em->expects(self::once())->method('flush');

        $this->service->delete($user);
    }
}
