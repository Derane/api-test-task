<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
use App\DTO\UserResponseDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @return UserResponseDTO[]
     */
    public function list(): array
    {
        $users = $this->userRepository->findAll();

        return array_map(UserResponseDTO::fromEntity(...), $users);
    }

    public function findOrFail(int $id): User
    {
        $user = $this->userRepository->find($id);

        if (null === $user) {
            throw new \DomainException('User not found.', 404);
        }

        return $user;
    }

    public function create(CreateUserDTO $dto): UserResponseDTO
    {
        $user = new User();
        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->pass));
        $user->setApiToken(bin2hex(random_bytes(32)));
        $user->setRoles(['ROLE_USER']);

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
