<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class ListTaskItem
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $status,
        public string $createdAt,
    ) {
    }
}
