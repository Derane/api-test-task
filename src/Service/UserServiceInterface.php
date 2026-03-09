<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
use App\DTO\UserResponseDTO;
use App\Entity\User;
use App\Exception\UserNotFoundException;

interface UserServiceInterface
{
    /** @return UserResponseDTO[] */
    public function list(): array;

    /** @throws UserNotFoundException */
    public function findOrFail(int $id): User;

    public function create(CreateUserDTO $dto): UserResponseDTO;

    public function update(User $user, UpdateUserDTO $dto): UserResponseDTO;

    public function delete(User $user): void;
}
