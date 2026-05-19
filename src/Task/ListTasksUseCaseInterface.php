<?php

declare(strict_types=1);

namespace Tasklog\Task;

interface ListTasksUseCaseInterface
{
    public function execute(ListTasksInput $input): ListTasksOutput;
}
