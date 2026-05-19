<?php

declare(strict_types=1);

namespace Tasklog\Application;

use LogicException;
use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Config\AppConfig;
use Nene2\Config\ConfigLoader;
use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ErrorHandlerMiddleware;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\ResponseEmitter;
use Nene2\Log\MonologLoggerFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Middleware\CorsMiddleware;
use Nene2\Middleware\MiddlewareDispatcher;
use Nene2\Middleware\RequestIdMiddleware;
use Nene2\Middleware\RequestLoggingMiddleware;
use Nene2\Middleware\RequestSizeLimitMiddleware;
use Nene2\Middleware\SecurityHeadersMiddleware;
use Nene2\Routing\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Tasklog\Auth\AuthRouteRegistrar;
use Tasklog\Auth\AuthServiceProvider;
use Tasklog\Auth\EmailAlreadyTakenExceptionHandler;
use Tasklog\Auth\InvalidCredentialsExceptionHandler;
use Tasklog\Http\TaskBearerAuthMiddleware;
use Tasklog\Task\TaskForbiddenExceptionHandler;
use Tasklog\Task\TaskNotFoundExceptionHandler;
use Tasklog\Task\TaskRouteRegistrar;
use Tasklog\Task\TaskServiceProvider;

final readonly class AppServiceProvider implements ServiceProviderInterface
{
    public const PROJECT_ROOT = 'tasklog.project_root';

    public function register(ContainerBuilder $builder): void
    {
        $builder->addProvider(new AuthServiceProvider());
        $builder->addProvider(new TaskServiceProvider());

        $builder
            ->set(
                ConfigLoader::class,
                static function (ContainerInterface $c): ConfigLoader {
                    $root = $c->get(self::PROJECT_ROOT);

                    if (!is_string($root) || $root === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new ConfigLoader($root);
                },
            )
            ->set(
                AppConfig::class,
                static function (ContainerInterface $c): AppConfig {
                    $loader = $c->get(ConfigLoader::class);

                    if (!$loader instanceof ConfigLoader) {
                        throw new LogicException('ConfigLoader service is invalid.');
                    }

                    return $loader->load();
                },
            )
            ->set(
                DatabaseConnectionFactoryInterface::class,
                static function (ContainerInterface $c): DatabaseConnectionFactoryInterface {
                    $config = $c->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('AppConfig service is invalid.');
                    }

                    return new PdoConnectionFactory($config->database);
                },
            )
            ->set(
                DatabaseQueryExecutorInterface::class,
                static function (ContainerInterface $c): DatabaseQueryExecutorInterface {
                    $connFactory = $c->get(DatabaseConnectionFactoryInterface::class);

                    if (!$connFactory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('DatabaseConnectionFactory service is invalid.');
                    }

                    return new PdoDatabaseQueryExecutor($connFactory);
                },
            )
            ->set(Psr17Factory::class, static fn (ContainerInterface $c): Psr17Factory => new Psr17Factory())
            ->set(
                ResponseFactoryInterface::class,
                static function (ContainerInterface $c): ResponseFactoryInterface {
                    $factory = $c->get(Psr17Factory::class);

                    if (!$factory instanceof ResponseFactoryInterface) {
                        throw new LogicException('Psr17Factory service is invalid.');
                    }

                    return $factory;
                },
            )
            ->set(
                StreamFactoryInterface::class,
                static function (ContainerInterface $c): StreamFactoryInterface {
                    $factory = $c->get(Psr17Factory::class);

                    if (!$factory instanceof StreamFactoryInterface) {
                        throw new LogicException('Psr17Factory service is invalid.');
                    }

                    return $factory;
                },
            )
            ->set(
                JsonResponseFactory::class,
                static function (ContainerInterface $c): JsonResponseFactory {
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $streamFactory = $c->get(StreamFactoryInterface::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('StreamFactory service is invalid.');
                    }

                    return new JsonResponseFactory($responseFactory, $streamFactory);
                },
            )
            ->set(
                ProblemDetailsResponseFactory::class,
                static function (ContainerInterface $c): ProblemDetailsResponseFactory {
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $streamFactory = $c->get(StreamFactoryInterface::class);
                    $config = $c->get(AppConfig::class);

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactory service is invalid.');
                    }

                    if (!$streamFactory instanceof StreamFactoryInterface) {
                        throw new LogicException('StreamFactory service is invalid.');
                    }

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('AppConfig service is invalid.');
                    }

                    return new ProblemDetailsResponseFactory(
                        $responseFactory,
                        $streamFactory,
                        $config->problemDetailsBaseUrl,
                    );
                },
            )
            ->set(RequestIdHolder::class, static fn (ContainerInterface $c): RequestIdHolder => new RequestIdHolder())
            ->set(
                LoggerInterface::class,
                static function (ContainerInterface $c): LoggerInterface {
                    $config = $c->get(AppConfig::class);
                    $debug = $config instanceof AppConfig && $config->debug;
                    $holder = $c->get(RequestIdHolder::class);

                    return (new MonologLoggerFactory())->create(
                        'tasklog',
                        $debug,
                        $holder instanceof RequestIdHolder ? $holder : null,
                    );
                },
            )
            ->set(
                TokenVerifierInterface::class,
                static function (ContainerInterface $c): TokenVerifierInterface {
                    $verifier = $c->get(LocalBearerTokenVerifier::class);

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('LocalBearerTokenVerifier service is invalid.');
                    }

                    return $verifier;
                },
            )
            ->set(
                TaskBearerAuthMiddleware::class,
                static function (ContainerInterface $c): TaskBearerAuthMiddleware {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);
                    $verifier = $c->get(TokenVerifierInterface::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('TokenVerifier service is invalid.');
                    }

                    return new TaskBearerAuthMiddleware($problemDetails, $verifier);
                },
            )
            ->set(ResponseEmitter::class, static fn (ContainerInterface $c): ResponseEmitter => new ResponseEmitter())
            ->set(
                RequestHandlerInterface::class,
                static function (ContainerInterface $c): RequestHandlerInterface {
                    $responseFactory = $c->get(ResponseFactoryInterface::class);
                    $logger = $c->get(LoggerInterface::class);
                    $config = $c->get(AppConfig::class);
                    $requestIdHolder = $c->get(RequestIdHolder::class);
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);
                    $jsonResponse = $c->get(JsonResponseFactory::class);
                    $bearerMiddleware = $c->get(TaskBearerAuthMiddleware::class);

                    $emailAlreadyTakenHandler = $c->get(EmailAlreadyTakenExceptionHandler::class);
                    $invalidCredentialsHandler = $c->get(InvalidCredentialsExceptionHandler::class);
                    $taskNotFoundHandler = $c->get(TaskNotFoundExceptionHandler::class);
                    $taskForbiddenHandler = $c->get(TaskForbiddenExceptionHandler::class);

                    $authRegistrar = $c->get('tasklog.route_registrar.auth');
                    $taskRegistrar = $c->get('tasklog.route_registrar.task');

                    if (!$responseFactory instanceof ResponseFactoryInterface) {
                        throw new LogicException('ResponseFactory service is invalid.');
                    }

                    if (!$logger instanceof LoggerInterface) {
                        throw new LogicException('Logger service is invalid.');
                    }

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('AppConfig service is invalid.');
                    }

                    if (!$requestIdHolder instanceof RequestIdHolder) {
                        throw new LogicException('RequestIdHolder service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    if (!$jsonResponse instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    if (!$bearerMiddleware instanceof TaskBearerAuthMiddleware) {
                        throw new LogicException('TaskBearerAuthMiddleware service is invalid.');
                    }

                    if (!$emailAlreadyTakenHandler instanceof EmailAlreadyTakenExceptionHandler) {
                        throw new LogicException('EmailAlreadyTakenExceptionHandler service is invalid.');
                    }

                    if (!$invalidCredentialsHandler instanceof InvalidCredentialsExceptionHandler) {
                        throw new LogicException('InvalidCredentialsExceptionHandler service is invalid.');
                    }

                    if (!$taskNotFoundHandler instanceof TaskNotFoundExceptionHandler) {
                        throw new LogicException('TaskNotFoundExceptionHandler service is invalid.');
                    }

                    if (!$taskForbiddenHandler instanceof TaskForbiddenExceptionHandler) {
                        throw new LogicException('TaskForbiddenExceptionHandler service is invalid.');
                    }

                    if (!$authRegistrar instanceof AuthRouteRegistrar) {
                        throw new LogicException('AuthRouteRegistrar service is invalid.');
                    }

                    if (!$taskRegistrar instanceof TaskRouteRegistrar) {
                        throw new LogicException('TaskRouteRegistrar service is invalid.');
                    }

                    /** @var list<DomainExceptionHandlerInterface> $domainHandlers */
                    $domainHandlers = [
                        $emailAlreadyTakenHandler,
                        $invalidCredentialsHandler,
                        $taskNotFoundHandler,
                        $taskForbiddenHandler,
                    ];

                    $router = new Router();
                    $router->get(
                        '/',
                        static fn (ServerRequestInterface $r) => $jsonResponse->create([
                            'name'   => 'tasklog',
                            'status' => 'ok',
                        ]),
                    );

                    $authRegistrar($router);
                    $taskRegistrar($router);

                    return new MiddlewareDispatcher(
                        [
                            new RequestIdMiddleware('X-Request-Id', $requestIdHolder),
                            new RequestLoggingMiddleware($logger),
                            new SecurityHeadersMiddleware(),
                            new CorsMiddleware($responseFactory),
                            new ErrorHandlerMiddleware($problemDetails, $domainHandlers),
                            new RequestSizeLimitMiddleware($problemDetails),
                            $bearerMiddleware,
                        ],
                        $router,
                    );
                },
            );
    }
}
