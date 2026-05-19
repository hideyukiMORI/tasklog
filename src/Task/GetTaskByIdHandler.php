<?php

declare(strict_types=1);

namespace Tasklog\Task;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetTaskByIdHandler
{
    public function __construct(
        private GetTaskByIdUseCaseInterface $useCase,
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

        $output = $this->useCase->execute(new GetTaskByIdInput(
            taskId: $id,
            requestingUserId: $userId,
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
