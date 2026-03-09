<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\CreateUserDTO;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserFactory implements UserFactoryInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function create(CreateUserDTO $dto): User
    {
        $user = new User();
        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->pass));
        $user->setApiToken(bin2hex(random_bytes(32)));
        $user->setRoles(['ROLE_USER']);

        return $user;
    }
}
