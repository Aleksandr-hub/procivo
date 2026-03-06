<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Board;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Kanban or process board')]
final readonly class BoardDTO
{
    /**
     * @param list<BoardColumnDTO> $columns
     */
    public function __construct(
        #[OA\Property(description: 'Board UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Board name')]
        public string $name,
        #[OA\Property(description: 'Board description', nullable: true)]
        public ?string $description,
        #[OA\Property(description: 'Board type', enum: ['kanban', 'process'])]
        public string $boardType,
        #[OA\Property(description: 'Linked process definition UUID (for process boards)', format: 'uuid', nullable: true)]
        public ?string $processDefinitionId,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Last update timestamp', format: 'date-time', nullable: true)]
        public ?string $updatedAt,
        #[OA\Property(description: 'Board columns', type: 'array', items: new OA\Items(ref: new \Nelmio\ApiDocBundle\Attribute\Model(type: \App\TaskManager\Application\DTO\BoardColumnDTO::class)))]
        public array $columns = [],
    ) {
    }

    /**
     * @param list<BoardColumnDTO> $columns
     */
    public static function fromEntity(Board $board, array $columns = []): self
    {
        return new self(
            id: $board->id()->value(),
            organizationId: $board->organizationId()->value(),
            name: $board->name(),
            description: $board->description(),
            boardType: $board->boardType(),
            processDefinitionId: $board->processDefinitionId(),
            createdAt: $board->createdAt()->value()->format(\DateTimeInterface::ATOM),
            updatedAt: $board->updatedAt()?->format(\DateTimeInterface::ATOM),
            columns: $columns,
        );
    }
}
