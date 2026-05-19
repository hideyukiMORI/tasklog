<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class UpdateTaskInput
{
    public function __construct(
        public int $taskId,
        public int $requestingUserId,
        public string $title,
        public string $description,
        public string $status,
    ) {
    }
}
