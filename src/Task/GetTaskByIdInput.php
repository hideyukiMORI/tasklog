<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class GetTaskByIdInput
{
    public function __construct(
        public int $taskId,
        public int $requestingUserId,
    ) {
    }
}
