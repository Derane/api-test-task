<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\Security\ApiTokenHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class ApiTokenHandlerTest extends TestCase
{
    public function testValidToken(): void
    {
        $user = new User();
        $user->setLogin('testuser');

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByApiToken')->with('valid-token')->willReturn($user);

        $handler = new ApiTokenHandler($repo);
        $badge = $handler->getUserBadgeFrom('valid-token');

        self::assertSame('testuser', $badge->getUserIdentifier());
    }

    public function testInvalidTokenThrows(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByApiToken')->willReturn(null);

        $handler = new ApiTokenHandler($repo);

        $this->expectException(BadCredentialsException::class);
        $handler->getUserBadgeFrom('invalid-token');
    }
}
