<?php

declare(strict_types=1);

namespace Tasklog\Task;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class TaskRouteRegistrar
{
    public function __construct(
        private ListTasksHandler $listHandler,
        private CreateTaskHandler $createHandler,
        private GetTaskByIdHandler $getHandler,
        private UpdateTaskHandler $updateHandler,
        private DeleteTaskHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $create = $this->createHandler;
        $get = $this->getHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;

        $router->get('/tasks', static fn (ServerRequestInterface $r) => $list->handle($r));
        $router->post('/tasks', static fn (ServerRequestInterface $r) => $create->handle($r));
        $router->get('/tasks/{id}', static fn (ServerRequestInterface $r) => $get->handle($r));
        $router->put('/tasks/{id}', static fn (ServerRequestInterface $r) => $update->handle($r));
        $router->delete('/tasks/{id}', static fn (ServerRequestInterface $r) => $delete->handle($r));
    }
}
