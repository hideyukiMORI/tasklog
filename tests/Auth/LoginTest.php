<?php

declare(strict_types=1);

namespace Tasklog\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Tasklog\Auth\InvalidCredentialsException;
use Tasklog\Auth\LoginInput;
use Tasklog\Auth\LoginUseCase;
use Tasklog\Auth\TokenIssuerInterface;
use Tasklog\Auth\User;
use Tasklog\Auth\UserRepositoryInterface;

final class LoginTest extends TestCase
{
    public function testLoginWithValidCredentialsReturnsToken(): void
    {
        $hash = password_hash('secret123', PASSWORD_BCRYPT);
        $user = new User(email: 'alice@example.com', passwordHash: $hash, id: 42);

        $repo = new class ($user) implements UserRepositoryInterface {
            public function __construct(private readonly User $storedUser)
            {
            }

            public function findByEmail(string $email): ?User
            {
                return $email === $this->storedUser->email ? $this->storedUser : null;
            }

            public function save(User $user): int
            {
                return 0;
            }
        };

        $issuer = new class () implements TokenIssuerInterface {
            /** @param array<string, mixed> $claims */
            public function issue(array $claims): string
            {
                return 'test.jwt.token';
            }
        };

        $useCase = new LoginUseCase($repo, $issuer);
        $output = $useCase->execute(new LoginInput('alice@example.com', 'secret123'));

        $this->assertSame('test.jwt.token', $output->token);
        $this->assertSame('Bearer', $output->tokenType);
        $this->assertSame(86400, $output->expiresIn);
    }

    public function testLoginWithWrongPasswordThrows(): void
    {
        $hash = password_hash('correctpassword', PASSWORD_BCRYPT);
        $user = new User(email: 'alice@example.com', passwordHash: $hash, id: 42);

        $repo = new class ($user) implements UserRepositoryInterface {
            public function __construct(private readonly User $storedUser)
            {
            }

            public function findByEmail(string $email): ?User
            {
                return $email !== '' ? $this->storedUser : null;
            }

            public function save(User $user): int
            {
                return 0;
            }
        };

        $issuer = new class () implements TokenIssuerInterface {
            /** @param array<string, mixed> $claims */
            public function issue(array $claims): string
            {
                return '';
            }
        };

        $this->expectException(InvalidCredentialsException::class);
        (new LoginUseCase($repo, $issuer))->execute(new LoginInput('alice@example.com', 'wrongpassword'));
    }

    public function testLoginWithUnknownEmailThrows(): void
    {
        $repo = new class () implements UserRepositoryInterface {
            public function findByEmail(string $email): ?User
            {
                return null;
            }

            public function save(User $user): int
            {
                return 0;
            }
        };

        $issuer = new class () implements TokenIssuerInterface {
            /** @param array<string, mixed> $claims */
            public function issue(array $claims): string
            {
                return '';
            }
        };

        $this->expectException(InvalidCredentialsException::class);
        (new LoginUseCase($repo, $issuer))->execute(new LoginInput('nobody@example.com', 'password'));
    }
}
