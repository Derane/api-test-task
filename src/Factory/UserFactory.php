<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\UserPayloadDTO;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserFactory implements UserFactoryInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function create(UserPayloadDTO $dto): User
    {
        $user = new User();
        $user->setApiToken(bin2hex(random_bytes(32)));
        $user->setRoles([User::ROLE_USER]);

        return $this->update($user, $dto);
    }

    public function update(User $user, UserPayloadDTO $dto): User
    {
        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->pass));

        return $user;
    }
}
