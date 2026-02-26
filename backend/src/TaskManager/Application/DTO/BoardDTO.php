<?php

declare(strict_types=1);

namespace App\TaskManager\Application\DTO;

use App\TaskManager\Domain\Entity\Board;

final readonly class BoardDTO
{
    /**
     * @param list<BoardColumnDTO> $columns
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $description,
        public string $createdAt,
        public ?string $updatedAt,
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
            createdAt: $board->createdAt()->value()->format(\DateTimeInterface::ATOM),
            updatedAt: $board->updatedAt()?->format(\DateTimeInterface::ATOM),
            columns: $columns,
        );
    }
}
