<?php

declare(strict_types=1);

namespace Tasklog\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->query->fetchOne(
            'SELECT id, email, password_hash, created_at FROM users WHERE email = ?',
            [$email],
        );

        if ($row === null) {
            return null;
        }

        return new User(
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            id: (int) $row['id'],
            createdAt: (string) $row['created_at'],
        );
    }

    public function save(User $user): int
    {
        $this->query->execute(
            'INSERT INTO users (email, password_hash) VALUES (?, ?)',
            [$user->email, $user->passwordHash],
        );

        return $this->query->lastInsertId();
    }
}
