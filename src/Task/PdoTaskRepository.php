<?php

declare(strict_types=1);

namespace Tasklog\Task;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(int $id): ?Task
    {
        $row = $this->query->fetchOne(
            'SELECT id, user_id, title, description, status, created_at FROM tasks WHERE id = ?',
            [$id],
        );

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return list<Task> */
    public function findByUserId(int $userId, int $limit, int $offset): array
    {
        $rows = $this->query->fetchAll(
            'SELECT id, user_id, title, description, status, created_at FROM tasks WHERE user_id = ? ORDER BY id ASC LIMIT ? OFFSET ?',
            [$userId, $limit, $offset],
        );

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function countByUserId(int $userId): int
    {
        $row = $this->query->fetchOne(
            'SELECT COUNT(*) as cnt FROM tasks WHERE user_id = ?',
            [$userId],
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function save(Task $task): int
    {
        $this->query->execute(
            'INSERT INTO tasks (user_id, title, description, status) VALUES (?, ?, ?, ?)',
            [$task->userId, $task->title, $task->description, $task->status],
        );

        return $this->query->lastInsertId();
    }

    public function update(Task $task): void
    {
        $this->query->execute(
            'UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ?',
            [$task->title, $task->description, $task->status, $task->id],
        );
    }

    public function delete(int $id): void
    {
        $this->query->execute('DELETE FROM tasks WHERE id = ?', [$id]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Task
    {
        return new Task(
            userId: (int) $row['user_id'],
            title: (string) $row['title'],
            description: (string) $row['description'],
            status: (string) $row['status'],
            id: (int) $row['id'],
            createdAt: (string) $row['created_at'],
        );
    }
}
