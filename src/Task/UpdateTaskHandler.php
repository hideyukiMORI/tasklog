<?php

declare(strict_types=1);

namespace Tasklog\Task;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateTaskHandler
{
    private const VALID_STATUSES = ['open', 'done'];

    public function __construct(
        private UpdateTaskUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->getAttribute('nene2.auth.claims', []);
        $userId = (int) ($claims['sub'] ?? 0);

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new TaskNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $title = trim((string) ($body['title'] ?? ''));
        $description = trim((string) ($body['description'] ?? ''));
        $status = trim((string) ($body['status'] ?? 'open'));

        $errors = [];

        if ($title === '') {
            $errors[] = new ValidationError('title', 'Title is required.', 'required');
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            $errors[] = new ValidationError('status', 'Status must be "open" or "done".', 'invalid_value');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new UpdateTaskInput(
            taskId: $id,
            requestingUserId: $userId,
            title: $title,
            description: $description,
            status: $status,
        ));

        return $this->response->create([
            'id'          => $output->id,
            'title'       => $output->title,
            'description' => $output->description,
            'status'      => $output->status,
            'created_at'  => $output->createdAt,
        ]);
    }
}
