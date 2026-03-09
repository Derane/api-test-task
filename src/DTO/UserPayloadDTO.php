<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserPayloadDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Login is required.')]
        #[Assert\Length(max: 8, maxMessage: 'Login must be at most {{ limit }} characters.')]
        public string $login = '',

        #[Assert\NotBlank(message: 'Phone is required.')]
        #[Assert\Length(max: 8, maxMessage: 'Phone must be at most {{ limit }} characters.')]
        public string $phone = '',

        #[Assert\NotBlank(message: 'Password is required.')]
        #[Assert\Length(max: 8, maxMessage: 'Password must be at most {{ limit }} characters.')]
        public string $pass = '',
    ) {
    }
}
