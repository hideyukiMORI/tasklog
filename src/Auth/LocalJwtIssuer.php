<?php

declare(strict_types=1);

namespace Tasklog\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;

final readonly class LocalJwtIssuer implements TokenIssuerInterface
{
    public function __construct(
        private LocalBearerTokenVerifier $verifier,
    ) {
    }

    /** @param array<string, mixed> $claims */
    public function issue(array $claims): string
    {
        return $this->verifier->issue($claims);
    }
}
