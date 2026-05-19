<?php

declare(strict_types=1);

use Nene2\Http\ResponseEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Server\RequestHandlerInterface;
use Tasklog\Application\AppContainerFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = (new AppContainerFactory(dirname(__DIR__)))->create();

$psr17Factory = $container->get(Psr17Factory::class);
assert($psr17Factory instanceof Psr17Factory);

$serverRequestCreator = new ServerRequestCreator(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
);

$request = $serverRequestCreator->fromGlobals();

$application = $container->get(RequestHandlerInterface::class);
assert($application instanceof RequestHandlerInterface);

$response = $application->handle($request);

$emitter = $container->get(ResponseEmitter::class);
assert($emitter instanceof ResponseEmitter);

$emitter->emit($response);
