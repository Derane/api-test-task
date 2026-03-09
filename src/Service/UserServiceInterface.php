<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\UserPayloadDTO;
use App\DTO\UserResponseDTO;
use App\Entity\User;

interface UserServiceInterface
{
    /** @return UserResponseDTO[] */
    public function list(User $currentUser): array;

    public function findOrFail(int $id): User;

    public function create(UserPayloadDTO $dto): UserResponseDTO;

    public function update(User $user, UserPayloadDTO $dto): UserResponseDTO;

    public function delete(User $user): void;
}
