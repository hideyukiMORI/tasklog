<?php

declare(strict_types=1);

namespace Tasklog\Auth;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LoginHandler
{
    public function __construct(
        private LoginUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email is required.', 'required');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'Password is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new LoginInput(email: $email, password: $password));

        return $this->response->create([
            'access_token' => $output->token,
            'token_type'   => $output->tokenType,
            'expires_in'   => $output->expiresIn,
        ]);
    }
}
