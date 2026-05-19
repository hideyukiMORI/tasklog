<?php

declare(strict_types=1);

namespace Tasklog\Task;

interface DeleteTaskUseCaseInterface
{
    /** @throws TaskNotFoundException|TaskForbiddenException */
    public function execute(DeleteTaskInput $input): void;
}
