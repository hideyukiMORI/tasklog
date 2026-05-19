<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class GetTaskByIdUseCase implements GetTaskByIdUseCaseInterface
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {
    }

    public function execute(GetTaskByIdInput $input): GetTaskByIdOutput
    {
        $task = $this->tasks->findById($input->taskId);

        if ($task === null) {
            throw new TaskNotFoundException($input->taskId);
        }

        if ($task->userId !== $input->requestingUserId) {
            throw new TaskForbiddenException($input->taskId);
        }

        return new GetTaskByIdOutput(
            id: $task->id,
            title: $task->title,
            description: $task->description,
            status: $task->status,
            createdAt: $task->createdAt,
        );
    }
}
