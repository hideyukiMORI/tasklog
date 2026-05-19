<?php

declare(strict_types=1);

namespace Tasklog\Task;

interface TaskRepositoryInterface
{
    public function findById(int $id): ?Task;

    /** @return list<Task> */
    public function findByUserId(int $userId, int $limit, int $offset): array;

    public function countByUserId(int $userId): int;

    public function save(Task $task): int;

    public function update(Task $task): void;

    public function delete(int $id): void;
}
