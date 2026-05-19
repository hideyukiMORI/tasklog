<?php

declare(strict_types=1);

namespace Tasklog\Tests\Task;

use PHPUnit\Framework\TestCase;
use Tasklog\Task\CreateTaskInput;
use Tasklog\Task\CreateTaskUseCase;
use Tasklog\Task\DeleteTaskInput;
use Tasklog\Task\DeleteTaskUseCase;
use Tasklog\Task\GetTaskByIdInput;
use Tasklog\Task\GetTaskByIdUseCase;
use Tasklog\Task\ListTasksInput;
use Tasklog\Task\ListTasksUseCase;
use Tasklog\Task\Task;
use Tasklog\Task\TaskForbiddenException;
use Tasklog\Task\TaskNotFoundException;
use Tasklog\Task\TaskRepositoryInterface;
use Tasklog\Task\UpdateTaskInput;
use Tasklog\Task\UpdateTaskUseCase;

final class TaskUseCaseTest extends TestCase
{
    public function testListTasksReturnsOwnedTasks(): void
    {
        $task = new Task(userId: 1, title: 'Do laundry', description: '', status: 'open', id: 10);
        $repo = $this->makeRepo([$task]);

        $output = (new ListTasksUseCase($repo))->execute(new ListTasksInput(userId: 1, limit: 20, offset: 0));

        $this->assertCount(1, $output->items);
        $this->assertSame('Do laundry', $output->items[0]->title);
        $this->assertSame(1, $output->total);
    }

    public function testCreateTaskReturnsSavedTask(): void
    {
        $repo = $this->makeRepo([]);

        $output = (new CreateTaskUseCase($repo))->execute(
            new CreateTaskInput(userId: 1, title: 'Buy milk', description: 'Full fat'),
        );

        $this->assertSame(1, $output->id);
        $this->assertSame('Buy milk', $output->title);
        $this->assertSame('open', $output->status);
    }

    public function testGetTaskByIdReturnsTask(): void
    {
        $task = new Task(userId: 5, title: 'Clean desk', description: '', status: 'open', id: 7);
        $repo = $this->makeRepo([$task]);

        $output = (new GetTaskByIdUseCase($repo))->execute(new GetTaskByIdInput(taskId: 7, requestingUserId: 5));

        $this->assertSame(7, $output->id);
        $this->assertSame('Clean desk', $output->title);
    }

    public function testGetTaskByIdThrowsForbiddenForOtherUser(): void
    {
        $task = new Task(userId: 5, title: 'Private task', description: '', status: 'open', id: 7);
        $repo = $this->makeRepo([$task]);

        $this->expectException(TaskForbiddenException::class);
        (new GetTaskByIdUseCase($repo))->execute(new GetTaskByIdInput(taskId: 7, requestingUserId: 99));
    }

    public function testGetTaskByIdThrowsNotFoundForMissingTask(): void
    {
        $repo = $this->makeRepo([]);

        $this->expectException(TaskNotFoundException::class);
        (new GetTaskByIdUseCase($repo))->execute(new GetTaskByIdInput(taskId: 999, requestingUserId: 1));
    }

    public function testUpdateTaskThrowsForbiddenForOtherUser(): void
    {
        $task = new Task(userId: 5, title: 'Original', description: '', status: 'open', id: 3);
        $repo = $this->makeRepo([$task]);

        $this->expectException(TaskForbiddenException::class);
        (new UpdateTaskUseCase($repo))->execute(
            new UpdateTaskInput(taskId: 3, requestingUserId: 99, title: 'Changed', description: '', status: 'done'),
        );
    }

    public function testDeleteTaskThrowsForbiddenForOtherUser(): void
    {
        $task = new Task(userId: 5, title: 'Secret', description: '', status: 'open', id: 4);
        $repo = $this->makeRepo([$task]);

        $this->expectException(TaskForbiddenException::class);
        (new DeleteTaskUseCase($repo))->execute(new DeleteTaskInput(taskId: 4, requestingUserId: 99));
    }

    /** @param list<Task> $tasks */
    private function makeRepo(array $tasks): TaskRepositoryInterface
    {
        return new class ($tasks) implements TaskRepositoryInterface {
            /** @param list<Task> $tasks */
            public function __construct(private array $tasks)
            {
            }

            public function findById(int $id): ?Task
            {
                foreach ($this->tasks as $task) {
                    if ($task->id === $id) {
                        return $task;
                    }
                }

                return null;
            }

            /** @return list<Task> */
            public function findByUserId(int $userId, int $limit, int $offset): array
            {
                return array_values(array_filter($this->tasks, static fn (Task $t) => $t->userId === $userId));
            }

            public function countByUserId(int $userId): int
            {
                return count(array_filter($this->tasks, static fn (Task $t) => $t->userId === $userId));
            }

            public function save(Task $task): int
            {
                $id = count($this->tasks) + 1;
                $this->tasks[] = new Task(
                    userId: $task->userId,
                    title: $task->title,
                    description: $task->description,
                    status: $task->status,
                    id: $id,
                );

                return $id;
            }

            public function update(Task $task): void
            {
                foreach ($this->tasks as $i => $t) {
                    if ($t->id === $task->id) {
                        $this->tasks[$i] = $task;

                        return;
                    }
                }
            }

            public function delete(int $id): void
            {
                $this->tasks = array_values(array_filter($this->tasks, static fn (Task $t) => $t->id !== $id));
            }
        };
    }
}
