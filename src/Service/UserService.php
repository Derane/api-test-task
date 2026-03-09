<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\UserPayloadDTO;
use App\DTO\UserResponseDTO;
use App\Entity\User;
use App\Exception\UserNotFoundException;
use App\Factory\UserFactoryInterface;
use App\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepositoryInterface $userRepository,
        private UserFactoryInterface $userFactory,
    ) {
    }

    /** @inheritDoc */
    public function list(User $currentUser): array
    {
        if ($currentUser->hasRole(User::ROLE_ROOT)) {
            return array_map(UserResponseDTO::fromEntity(...), $this->userRepository->findAll());
        }

        return [UserResponseDTO::fromEntity($currentUser)];
    }

    public function findOrFail(int $id): User
    {
        return $this->userRepository->findById($id)
            ?? throw new UserNotFoundException($id);
    }

    public function create(UserPayloadDTO $dto): UserResponseDTO
    {
        $user = $this->userFactory->create($dto);

        $this->em->persist($user);
        $this->em->flush();

        return UserResponseDTO::fromEntity($user);
    }

    public function update(User $user, UserPayloadDTO $dto): UserResponseDTO
    {
        $this->userFactory->update($user, $dto);

        $this->em->flush();

        return UserResponseDTO::fromEntity($user);
    }

    public function delete(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}
