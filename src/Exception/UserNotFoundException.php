<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UserNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('User with id %d not found.', $id));
    }
}
