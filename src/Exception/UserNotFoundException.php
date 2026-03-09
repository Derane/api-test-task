<?php

declare(strict_types=1);

namespace App\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('User with id %d not found.', $id));
    }
}
