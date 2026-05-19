<?php

declare(strict_types=1);

namespace Tasklog\Auth;

final readonly class LoginOutput
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public int $expiresIn,
    ) {
    }
}
