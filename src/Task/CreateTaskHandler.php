<?php

declare(strict_types=1);

namespace Tasklog\Task;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateTaskHandler
{
    public function __construct(
        private CreateTaskUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->getAttribute('nene2.auth.claims', []);
        $userId = (int) ($claims['sub'] ?? 0);

        $body = JsonRequestBodyParser::parse($request);

        $title = trim((string) ($body['title'] ?? ''));
        $description = trim((string) ($body['description'] ?? ''));

        if ($title === '') {
            throw new ValidationException([new ValidationError('title', 'Title is required.', 'required')]);
        }

        $output = $this->useCase->execute(new CreateTaskInput(
            userId: $userId,
            title: $title,
            description: $description,
        ));

        return $this->response->create(
            [
                'id'          => $output->id,
                'title'       => $output->title,
                'description' => $output->description,
                'status'      => $output->status,
                'created_at'  => $output->createdAt,
            ],
            201,
            ['Location' => '/tasks/' . $output->id],
        );
    }
}
