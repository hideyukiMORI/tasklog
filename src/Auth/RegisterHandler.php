<?php

declare(strict_types=1);

namespace Tasklog\Auth;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RegisterHandler
{
    public function __construct(
        private RegisterUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $errors = [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = new ValidationError('email', 'A valid email address is required.', 'invalid_email');
        }

        if (strlen($password) < 8) {
            $errors[] = new ValidationError('password', 'Password must be at least 8 characters.', 'too_short');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new RegisterInput(email: $email, password: $password));

        return $this->response->create(
            ['id' => $output->id, 'email' => $output->email, 'created_at' => $output->createdAt],
            201,
        );
    }
}
