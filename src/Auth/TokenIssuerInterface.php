<?php

declare(strict_types=1);

namespace Tasklog\Auth;

interface TokenIssuerInterface
{
    /** @param array<string, mixed> $claims */
    public function issue(array $claims): string;
}
