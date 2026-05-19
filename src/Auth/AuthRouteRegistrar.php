<?php

declare(strict_types=1);

namespace Tasklog\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AuthRouteRegistrar
{
    public function __construct(
        private RegisterHandler $registerHandler,
        private LoginHandler $loginHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $register = $this->registerHandler;
        $login = $this->loginHandler;

        $router->post('/auth/register', static fn (ServerRequestInterface $r) => $register->handle($r));
        $router->post('/auth/login', static fn (ServerRequestInterface $r) => $login->handle($r));
    }
}
