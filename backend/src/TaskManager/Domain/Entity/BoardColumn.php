<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Entity;

use App\TaskManager\Domain\ValueObject\BoardId;
use App\TaskManager\Domain\ValueObject\ColumnId;

class BoardColumn
{
    private string $id;
    private string $boardId;
    private string $name;
    private int $position;
    private ?string $statusMapping;
    private ?int $wipLimit;
    private ?string $color;
    private ?string $nodeId = null;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        ColumnId $id,
        BoardId $boardId,
        string $name,
        int $position,
        ?string $statusMapping = null,
        ?int $wipLimit = null,
        ?string $color = null,
        ?string $nodeId = null,
    ): self {
        $col = new self();
        $col->id = $id->value();
        $col->boardId = $boardId->value();
        $col->name = $name;
        $col->position = $position;
        $col->statusMapping = $statusMapping;
        $col->wipLimit = $wipLimit;
        $col->color = $color;
        $col->nodeId = $nodeId;
        $col->createdAt = new \DateTimeImmutable();

        return $col;
    }

    public function update(
        string $name,
        int $position,
        ?string $statusMapping = null,
        ?int $wipLimit = null,
        ?string $color = null,
    ): void {
        $this->name = $name;
        $this->position = $position;
        $this->statusMapping = $statusMapping;
        $this->wipLimit = $wipLimit;
        $this->color = $color;
    }

    public function changePosition(int $position): void
    {
        $this->position = $position;
    }

    public function id(): ColumnId
    {
        return ColumnId::fromString($this->id);
    }

    public function boardId(): BoardId
    {
        return BoardId::fromString($this->boardId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function statusMapping(): ?string
    {
        return $this->statusMapping;
    }

    public function wipLimit(): ?int
    {
        return $this->wipLimit;
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function nodeId(): ?string
    {
        return $this->nodeId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
