<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class DeleteTaskUseCase implements DeleteTaskUseCaseInterface
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {
    }

    public function execute(DeleteTaskInput $input): void
    {
        $task = $this->tasks->findById($input->taskId);

        if ($task === null) {
            throw new TaskNotFoundException($input->taskId);
        }

        if ($task->userId !== $input->requestingUserId) {
            throw new TaskForbiddenException($input->taskId);
        }

        $this->tasks->delete($input->taskId);
    }
}
