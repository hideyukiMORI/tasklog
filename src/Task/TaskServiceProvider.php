<?php

declare(strict_types=1);

namespace Tasklog\Task;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final readonly class TaskServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                TaskRepositoryInterface::class,
                static function (ContainerInterface $c): TaskRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoTaskRepository($query);
                },
            )
            ->set(
                ListTasksUseCaseInterface::class,
                static function (ContainerInterface $c): ListTasksUseCaseInterface {
                    $tasks = $c->get(TaskRepositoryInterface::class);

                    if (!$tasks instanceof TaskRepositoryInterface) {
                        throw new LogicException('TaskRepository service is invalid.');
                    }

                    return new ListTasksUseCase($tasks);
                },
            )
            ->set(
                CreateTaskUseCaseInterface::class,
                static function (ContainerInterface $c): CreateTaskUseCaseInterface {
                    $tasks = $c->get(TaskRepositoryInterface::class);

                    if (!$tasks instanceof TaskRepositoryInterface) {
                        throw new LogicException('TaskRepository service is invalid.');
                    }

                    return new CreateTaskUseCase($tasks);
                },
            )
            ->set(
                GetTaskByIdUseCaseInterface::class,
                static function (ContainerInterface $c): GetTaskByIdUseCaseInterface {
                    $tasks = $c->get(TaskRepositoryInterface::class);

                    if (!$tasks instanceof TaskRepositoryInterface) {
                        throw new LogicException('TaskRepository service is invalid.');
                    }

                    return new GetTaskByIdUseCase($tasks);
                },
            )
            ->set(
                UpdateTaskUseCaseInterface::class,
                static function (ContainerInterface $c): UpdateTaskUseCaseInterface {
                    $tasks = $c->get(TaskRepositoryInterface::class);

                    if (!$tasks instanceof TaskRepositoryInterface) {
                        throw new LogicException('TaskRepository service is invalid.');
                    }

                    return new UpdateTaskUseCase($tasks);
                },
            )
            ->set(
                DeleteTaskUseCaseInterface::class,
                static function (ContainerInterface $c): DeleteTaskUseCaseInterface {
                    $tasks = $c->get(TaskRepositoryInterface::class);

                    if (!$tasks instanceof TaskRepositoryInterface) {
                        throw new LogicException('TaskRepository service is invalid.');
                    }

                    return new DeleteTaskUseCase($tasks);
                },
            )
            ->set(
                ListTasksHandler::class,
                static function (ContainerInterface $c): ListTasksHandler {
                    $useCase = $c->get(ListTasksUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListTasksUseCaseInterface) {
                        throw new LogicException('ListTasksUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new ListTasksHandler($useCase, $response);
                },
            )
            ->set(
                CreateTaskHandler::class,
                static function (ContainerInterface $c): CreateTaskHandler {
                    $useCase = $c->get(CreateTaskUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof CreateTaskUseCaseInterface) {
                        throw new LogicException('CreateTaskUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new CreateTaskHandler($useCase, $response);
                },
            )
            ->set(
                GetTaskByIdHandler::class,
                static function (ContainerInterface $c): GetTaskByIdHandler {
                    $useCase = $c->get(GetTaskByIdUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof GetTaskByIdUseCaseInterface) {
                        throw new LogicException('GetTaskByIdUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new GetTaskByIdHandler($useCase, $response);
                },
            )
            ->set(
                UpdateTaskHandler::class,
                static function (ContainerInterface $c): UpdateTaskHandler {
                    $useCase = $c->get(UpdateTaskUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof UpdateTaskUseCaseInterface) {
                        throw new LogicException('UpdateTaskUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new UpdateTaskHandler($useCase, $response);
                },
            )
            ->set(
                DeleteTaskHandler::class,
                static function (ContainerInterface $c): DeleteTaskHandler {
                    $useCase = $c->get(DeleteTaskUseCaseInterface::class);
                    $responseFactory = $c->get(ResponseFactoryInterface::class);

                    if (!$useCase instanceof DeleteTaskUseCaseInterface) {
                        throw new LogicException('DeleteTaskUseCase service is invalid.');
                    }

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactory service is invalid.');
                    }

                    return new DeleteTaskHandler($useCase, $responseFactory);
                },
            )
            ->set(
                TaskNotFoundExceptionHandler::class,
                static function (ContainerInterface $c): TaskNotFoundExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new TaskNotFoundExceptionHandler($problemDetails);
                },
            )
            ->set(
                TaskForbiddenExceptionHandler::class,
                static function (ContainerInterface $c): TaskForbiddenExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new TaskForbiddenExceptionHandler($problemDetails);
                },
            )
            ->set(
                'tasklog.route_registrar.task',
                static function (ContainerInterface $c): TaskRouteRegistrar {
                    $list = $c->get(ListTasksHandler::class);
                    $create = $c->get(CreateTaskHandler::class);
                    $get = $c->get(GetTaskByIdHandler::class);
                    $update = $c->get(UpdateTaskHandler::class);
                    $delete = $c->get(DeleteTaskHandler::class);

                    if (!$list instanceof ListTasksHandler) {
                        throw new LogicException('ListTasksHandler service is invalid.');
                    }

                    if (!$create instanceof CreateTaskHandler) {
                        throw new LogicException('CreateTaskHandler service is invalid.');
                    }

                    if (!$get instanceof GetTaskByIdHandler) {
                        throw new LogicException('GetTaskByIdHandler service is invalid.');
                    }

                    if (!$update instanceof UpdateTaskHandler) {
                        throw new LogicException('UpdateTaskHandler service is invalid.');
                    }

                    if (!$delete instanceof DeleteTaskHandler) {
                        throw new LogicException('DeleteTaskHandler service is invalid.');
                    }

                    return new TaskRouteRegistrar($list, $create, $get, $update, $delete);
                },
            );
    }
}
