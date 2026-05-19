<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class Task
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $description,
        public string $status,
        public int $id = 0,
        public string $createdAt = '',
    ) {
    }
}
