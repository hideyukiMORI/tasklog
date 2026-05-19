<?php

declare(strict_types=1);

namespace Tasklog\Auth;

final readonly class User
{
    public function __construct(
        public string $email,
        public string $passwordHash,
        public int $id = 0,
        public string $createdAt = '',
    ) {
    }
}
