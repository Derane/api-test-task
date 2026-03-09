<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\UserResponseDTO;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserResponseDTOTest extends TestCase
{
    public function testFromEntity(): void
    {
        $user = new User();
        $user->setLogin('testuser');
        $user->setPhone('12345678');

        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, 42);

        $dto = UserResponseDTO::fromEntity($user);

        self::assertSame(42, $dto->id);
        self::assertSame('testuser', $dto->login);
        self::assertSame('12345678', $dto->phone);
    }
}
