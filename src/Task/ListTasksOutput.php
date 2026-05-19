<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class ListTasksOutput
{
    /** @param list<ListTaskItem> $items */
    public function __construct(
        public array $items,
        public int $limit,
        public int $offset,
        public int $total,
    ) {
    }
}
