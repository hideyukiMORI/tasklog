<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class ListTasksInput
{
    public function __construct(
        public int $userId,
        public int $limit,
        public int $offset,
    ) {
    }
}
