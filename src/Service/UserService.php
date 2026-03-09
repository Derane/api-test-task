<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
use App\DTO\UserResponseDTO;
use App\Entity\User;
use App\Exception\UserNotFoundException;
use App\Factory\UserFactoryInterface;
use App\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepositoryInterface $userRepository,
        private UserFactoryInterface $userFactory,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /** @inheritDoc */
    public function list(): array
    {
        $users = $this->userRepository->findAll();

        return array_map(UserResponseDTO::fromEntity(...), $users);
    }

    public function findOrFail(int $id): User
    {
        return $this->userRepository->findById($id)
            ?? throw new UserNotFoundException($id);
    }

    public function create(CreateUserDTO $dto): UserResponseDTO
    {
        $user = $this->userFactory->create($dto);

        $this->em->persist($user);
        $this->em->flush();

        return UserResponseDTO::fromEntity($user);
    }

    public function update(User $user, UpdateUserDTO $dto): UserResponseDTO
    {
        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->pass));

        $this->em->flush();

        return UserResponseDTO::fromEntity($user);
    }

    public function delete(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}
