<?php

declare(strict_types=1);

namespace Tasklog\Task;

use RuntimeException;

final class TaskForbiddenException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Access to task with id {$id} is forbidden.");
    }
}
