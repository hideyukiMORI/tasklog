<?php

declare(strict_types=1);

namespace Tasklog\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Tasklog\Auth\EmailAlreadyTakenException;
use Tasklog\Auth\RegisterInput;
use Tasklog\Auth\RegisterUseCase;
use Tasklog\Auth\User;
use Tasklog\Auth\UserRepositoryInterface;

final class RegisterTest extends TestCase
{
    public function testRegisterNewUserReturnsOutput(): void
    {
        $repo = new class () implements UserRepositoryInterface {
            public function findByEmail(string $email): ?User
            {
                return null;
            }

            public function save(User $user): int
            {
                return 1;
            }
        };

        $useCase = new RegisterUseCase($repo);
        $output = $useCase->execute(new RegisterInput('test@example.com', 'password123'));

        $this->assertSame(1, $output->id);
        $this->assertSame('test@example.com', $output->email);
    }

    public function testRegisterWithExistingEmailThrows(): void
    {
        $existing = new User(email: 'taken@example.com', passwordHash: 'hash', id: 1);

        $repo = new class ($existing) implements UserRepositoryInterface {
            public function __construct(private readonly User $existing)
            {
            }

            public function findByEmail(string $email): ?User
            {
                return $email !== '' ? $this->existing : null;
            }

            public function save(User $user): int
            {
                return 2;
            }
        };

        $this->expectException(EmailAlreadyTakenException::class);
        (new RegisterUseCase($repo))->execute(new RegisterInput('taken@example.com', 'password123'));
    }
}
