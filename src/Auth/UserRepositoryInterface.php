<?php

declare(strict_types=1);

namespace Tasklog\Auth;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function save(User $user): int;
}
