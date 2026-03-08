<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;

final readonly class UserResponseDTO
{
    public function __construct(
        public int $id,
        public string $login,
        public string $phone,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            login: $user->getLogin(),
            phone: $user->getPhone(),
        );
    }
}
