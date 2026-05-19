<?php

declare(strict_types=1);

namespace Tasklog\Auth;

final readonly class RegisterOutput
{
    public function __construct(
        public int $id,
        public string $email,
        public string $createdAt,
    ) {
    }
}
