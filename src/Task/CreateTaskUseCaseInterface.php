<?php

declare(strict_types=1);

namespace Tasklog\Task;

interface CreateTaskUseCaseInterface
{
    public function execute(CreateTaskInput $input): CreateTaskOutput;
}
