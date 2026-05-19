<?php

declare(strict_types=1);

namespace Tasklog\Auth;

use LogicException;
use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Config\AppConfig;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Container\ContainerInterface;

final readonly class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                LocalBearerTokenVerifier::class,
                static function (ContainerInterface $c): LocalBearerTokenVerifier {
                    $config = $c->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('AppConfig service is invalid.');
                    }

                    $secret = $config->localJwtSecret ?? 'changeme';

                    return new LocalBearerTokenVerifier($secret);
                },
            )
            ->set(
                TokenIssuerInterface::class,
                static function (ContainerInterface $c): TokenIssuerInterface {
                    $verifier = $c->get(LocalBearerTokenVerifier::class);

                    if (!$verifier instanceof LocalBearerTokenVerifier) {
                        throw new LogicException('LocalBearerTokenVerifier service is invalid.');
                    }

                    return new LocalJwtIssuer($verifier);
                },
            )
            ->set(
                UserRepositoryInterface::class,
                static function (ContainerInterface $c): UserRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoUserRepository($query);
                },
            )
            ->set(
                RegisterUseCaseInterface::class,
                static function (ContainerInterface $c): RegisterUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepository service is invalid.');
                    }

                    return new RegisterUseCase($users);
                },
            )
            ->set(
                LoginUseCaseInterface::class,
                static function (ContainerInterface $c): LoginUseCaseInterface {
                    $users = $c->get(UserRepositoryInterface::class);
                    $issuer = $c->get(TokenIssuerInterface::class);

                    if (!$users instanceof UserRepositoryInterface) {
                        throw new LogicException('UserRepository service is invalid.');
                    }

                    if (!$issuer instanceof TokenIssuerInterface) {
                        throw new LogicException('TokenIssuer service is invalid.');
                    }

                    return new LoginUseCase($users, $issuer);
                },
            )
            ->set(
                RegisterHandler::class,
                static function (ContainerInterface $c): RegisterHandler {
                    $useCase = $c->get(RegisterUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof RegisterUseCaseInterface) {
                        throw new LogicException('RegisterUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new RegisterHandler($useCase, $response);
                },
            )
            ->set(
                LoginHandler::class,
                static function (ContainerInterface $c): LoginHandler {
                    $useCase = $c->get(LoginUseCaseInterface::class);
                    $response = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof LoginUseCaseInterface) {
                        throw new LogicException('LoginUseCase service is invalid.');
                    }

                    if (!$response instanceof JsonResponseFactory) {
                        throw new LogicException('JsonResponseFactory service is invalid.');
                    }

                    return new LoginHandler($useCase, $response);
                },
            )
            ->set(
                EmailAlreadyTakenExceptionHandler::class,
                static function (ContainerInterface $c): EmailAlreadyTakenExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new EmailAlreadyTakenExceptionHandler($problemDetails);
                },
            )
            ->set(
                InvalidCredentialsExceptionHandler::class,
                static function (ContainerInterface $c): InvalidCredentialsExceptionHandler {
                    $problemDetails = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('ProblemDetailsResponseFactory service is invalid.');
                    }

                    return new InvalidCredentialsExceptionHandler($problemDetails);
                },
            )
            ->set(
                'tasklog.route_registrar.auth',
                static function (ContainerInterface $c): AuthRouteRegistrar {
                    $register = $c->get(RegisterHandler::class);
                    $login = $c->get(LoginHandler::class);

                    if (!$register instanceof RegisterHandler) {
                        throw new LogicException('RegisterHandler service is invalid.');
                    }

                    if (!$login instanceof LoginHandler) {
                        throw new LogicException('LoginHandler service is invalid.');
                    }

                    return new AuthRouteRegistrar($register, $login);
                },
            );
    }
}
