<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\UserPayloadDTO;
use App\Entity\User;

interface UserFactoryInterface
{
    public function create(UserPayloadDTO $dto): User;

    public function update(User $user, UserPayloadDTO $dto): User;
}
