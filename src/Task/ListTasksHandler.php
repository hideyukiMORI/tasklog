<?php

declare(strict_types=1);

namespace Tasklog\Task;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListTasksHandler
{
    public function __construct(
        private ListTasksUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->getAttribute('nene2.auth.claims', []);
        $userId = (int) ($claims['sub'] ?? 0);

        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListTasksInput(
            userId: $userId,
            limit: $pagination->limit,
            offset: $pagination->offset,
        ));

        return $this->response->create([
            'items'  => array_map(
                static fn (ListTaskItem $item) => [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                    'status'      => $item->status,
                    'created_at'  => $item->createdAt,
                ],
                $output->items,
            ),
            'limit'  => $output->limit,
            'offset' => $output->offset,
            'total'  => $output->total,
        ]);
    }
}
