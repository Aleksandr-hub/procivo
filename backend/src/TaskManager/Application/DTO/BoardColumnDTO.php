<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\BoardColumn;

final readonly class BoardColumnDTO
{
    public function __construct(
        public string $id,
        public string $boardId,
        public string $name,
        public int $position,
        public ?string $statusMapping,
        public ?int $wipLimit,
        public ?string $color,
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
            createdAt: $column->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
