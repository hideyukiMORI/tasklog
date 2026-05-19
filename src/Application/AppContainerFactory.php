<?php

declare(strict_types=1);

namespace Tasklog\Application;

use Nene2\DependencyInjection\ContainerBuilder;
use Psr\Container\ContainerInterface;

final readonly class AppContainerFactory
{
    public function __construct(
        private string $projectRoot,
    ) {
    }

    public function create(): ContainerInterface
    {
        return (new ContainerBuilder())
            ->value(AppServiceProvider::PROJECT_ROOT, $this->projectRoot)
            ->addProvider(new AppServiceProvider())
            ->build();
    }
}
