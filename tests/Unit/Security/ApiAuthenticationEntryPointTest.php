<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\ApiAuthenticationEntryPoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class ApiAuthenticationEntryPointTest extends TestCase
{
    private ApiAuthenticationEntryPoint $entryPoint;

    protected function setUp(): void
    {
        $this->entryPoint = new ApiAuthenticationEntryPoint();
    }

    public function testStartReturns401WithAuthenticationRequired(): void
    {
        $request = Request::create('/v1/api/users');
        $response = $this->entryPoint->start($request);

        self::assertSame(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertSame('Authentication required.', $data['message']);
    }

    public function testOnAuthenticationFailureReturns401WithInvalidCredentials(): void
    {
        $request = Request::create('/v1/api/users');
        $exception = new AuthenticationException('test');
        $response = $this->entryPoint->onAuthenticationFailure($request, $exception);

        self::assertSame(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertSame('Invalid credentials.', $data['message']);
    }
}
