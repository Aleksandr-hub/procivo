<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\BoardColumn;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Board column representing a task status stage')]
final readonly class BoardColumnDTO
{
    public function __construct(
        #[OA\Property(description: 'Column UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Board UUID', format: 'uuid')]
        public string $boardId,
        #[OA\Property(description: 'Column name')]
        public string $name,
        #[OA\Property(description: 'Column position (left to right)')]
        public int $position,
        #[OA\Property(description: 'Mapped task status', nullable: true, example: 'in_progress')]
        public ?string $statusMapping,
        #[OA\Property(description: 'Work-in-progress limit', nullable: true)]
        public ?int $wipLimit,
        #[OA\Property(description: 'Column color hex code', nullable: true, example: '#3B82F6')]
        public ?string $color,
        #[OA\Property(description: 'Mapped workflow node UUID (process boards)', format: 'uuid', nullable: true)]
        public ?string $nodeId,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
        public string $createdAt,
    ) {
    }

    public static function fromEntity(BoardColumn $column): self
    {
        return new self(
            id: $column->id()->value(),
            boardId: $column->boardId()->value(),
            name: $column->name(),
            position: $column->position(),
            statusMapping: $column->statusMapping(),
            wipLimit: $column->wipLimit(),
            color: $column->color(),
            nodeId: $column->nodeId(),
            createdAt: $column->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
