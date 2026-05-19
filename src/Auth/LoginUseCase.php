<?php

declare(strict_types=1);

namespace Tasklog\Auth;

final readonly class LoginUseCase implements LoginUseCaseInterface
{
    private const TTL = 86400;

    public function __construct(
        private UserRepositoryInterface $users,
        private TokenIssuerInterface $issuer,
    ) {
    }

    public function execute(LoginInput $input): LoginOutput
    {
        $user = $this->users->findByEmail($input->email);

        if ($user === null || !password_verify($input->password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        $token = $this->issuer->issue([
            'sub'   => $user->id,
            'email' => $user->email,
            'iat'   => time(),
            'exp'   => time() + self::TTL,
        ]);

        return new LoginOutput(
            token: $token,
            tokenType: 'Bearer',
            expiresIn: self::TTL,
        );
    }
}
