<?php

declare(strict_types=1);

namespace Tasklog\Auth;

use RuntimeException;

final class EmailAlreadyTakenException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct("Email address '{$email}' is already registered.");
    }
}
