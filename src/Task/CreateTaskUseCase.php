<?php

declare(strict_types=1);

namespace Tasklog\Task;

final readonly class CreateTaskUseCase implements CreateTaskUseCaseInterface
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {
    }

    public function execute(CreateTaskInput $input): CreateTaskOutput
    {
        $id = $this->tasks->save(new Task(
            userId: $input->userId,
            title: $input->title,
            description: $input->description,
            status: 'open',
        ));

        return new CreateTaskOutput(
            id: $id,
            title: $input->title,
            description: $input->description,
            status: 'open',
            createdAt: '',
        );
    }
}
