<?php

declare(strict_types=1);

namespace Tasklog\Task;

interface GetTaskByIdUseCaseInterface
{
    /** @throws TaskNotFoundException|TaskForbiddenException */
    public function execute(GetTaskByIdInput $input): GetTaskByIdOutput;
}
