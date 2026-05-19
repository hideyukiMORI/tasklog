<?php

declare(strict_types=1);

namespace Tasklog\Auth;

final readonly class RegisterUseCase implements RegisterUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(RegisterInput $input): RegisterOutput
    {
        if ($this->users->findByEmail($input->email) !== null) {
            throw new EmailAlreadyTakenException($input->email);
        }

        $hash = password_hash($input->password, PASSWORD_BCRYPT);
        $id = $this->users->save(new User(email: $input->email, passwordHash: $hash));

        return new RegisterOutput(
            id: $id,
            email: $input->email,
            createdAt: '',
        );
    }
}
