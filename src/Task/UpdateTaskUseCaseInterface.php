<?php

declare(strict_types=1);

namespace Tasklog\Task;

interface UpdateTaskUseCaseInterface
{
    /** @throws TaskNotFoundException|TaskForbiddenException */
    public function execute(UpdateTaskInput $input): UpdateTaskOutput;
}
