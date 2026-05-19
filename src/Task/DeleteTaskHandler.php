<?php

declare(strict_types=1);

namespace Tasklog\Task;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteTaskHandler
{
    public function __construct(
        private DeleteTaskUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
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

        $this->useCase->execute(new DeleteTaskInput(
            taskId: $id,
            requestingUserId: $userId,
        ));

        return $this->responseFactory->createResponse(204);
    }
}
