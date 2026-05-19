<?php

declare(strict_types=1);

namespace Tasklog\Task;

use RuntimeException;

final class TaskNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Task with id {$id} was not found.");
    }
}
