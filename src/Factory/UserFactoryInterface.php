<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\CreateUserDTO;
use App\Entity\User;

interface UserFactoryInterface
{
    public function create(CreateUserDTO $dto): User;
}
