<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class ListTasksUseCase implements ListTasksUseCaseInterface
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {
    }

    public function execute(ListTasksInput $input): ListTasksOutput
    {
        $tasks = $this->tasks->findByUserId($input->userId, $input->limit, $input->offset);
        $total = $this->tasks->countByUserId($input->userId);

        return new ListTasksOutput(
            items: array_map(
                static fn (Task $t) => new ListTaskItem(
                    id: $t->id,
                    title: $t->title,
                    description: $t->description,
                    status: $t->status,
                    createdAt: $t->createdAt,
                ),
                $tasks,
            ),
            limit: $input->limit,
            offset: $input->offset,
            total: $total,
        );
    }
}
