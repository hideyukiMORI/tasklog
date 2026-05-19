<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class UpdateTaskUseCase implements UpdateTaskUseCaseInterface
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {
    }

    public function execute(UpdateTaskInput $input): UpdateTaskOutput
    {
        $task = $this->tasks->findById($input->taskId);

        if ($task === null) {
            throw new TaskNotFoundException($input->taskId);
        }

        if ($task->userId !== $input->requestingUserId) {
            throw new TaskForbiddenException($input->taskId);
        }

        $updated = new Task(
            userId: $task->userId,
            title: $input->title,
            description: $input->description,
            status: $input->status,
            id: $task->id,
            createdAt: $task->createdAt,
        );

        $this->tasks->update($updated);

        return new UpdateTaskOutput(
            id: $task->id,
            title: $input->title,
            description: $input->description,
            status: $input->status,
            createdAt: $task->createdAt,
        );
    }
}
