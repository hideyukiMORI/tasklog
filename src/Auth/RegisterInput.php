<?php

declare(strict_types=1);

namespace Tasklog\Auth;

final readonly class RegisterInput
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
