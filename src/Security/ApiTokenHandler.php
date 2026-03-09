<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $user = $this->userRepository->findByApiToken($accessToken);

        if (null === $user) {
            throw new BadCredentialsException('Invalid API token.');
        }

        return new UserBadge($user->getUserIdentifier());
    }
}
