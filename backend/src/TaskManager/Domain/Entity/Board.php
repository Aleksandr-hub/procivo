<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Entity;

use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\TaskManager\Domain\Event\BoardCreatedEvent;
use App\TaskManager\Domain\ValueObject\BoardId;

class Board extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private string $name;
    private ?string $description;
    private string $boardType = 'task_board';
    private ?string $processDefinitionId = null;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        BoardId $id,
        OrganizationId $organizationId,
        string $name,
        ?string $description = null,
    ): self {
        $board = new self();
        $board->id = $id->value();
        $board->organizationId = $organizationId->value();
        $board->name = $name;
        $board->description = $description;
        $board->boardType = 'task_board';
        $board->processDefinitionId = null;
        $board->createdAt = new \DateTimeImmutable();
        $board->updatedAt = null;

        $board->recordEvent(new BoardCreatedEvent(
            $id->value(),
            $organizationId->value(),
            $name,
        ));

        return $board;
    }

    public static function createProcessBoard(
        BoardId $id,
        OrganizationId $organizationId,
        string $name,
        string $processDefinitionId,
    ): self {
        $board = new self();
        $board->id = $id->value();
        $board->organizationId = $organizationId->value();
        $board->name = $name;
        $board->description = null;
        $board->boardType = 'process_board';
        $board->processDefinitionId = $processDefinitionId;
        $board->createdAt = new \DateTimeImmutable();
        $board->updatedAt = null;

        $board->recordEvent(new BoardCreatedEvent(
            $id->value(),
            $organizationId->value(),
            $name,
        ));

        return $board;
    }

    public function update(string $name, ?string $description): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): BoardId
    {
        return BoardId::fromString($this->id);
    }

    public function organizationId(): OrganizationId
    {
        return OrganizationId::fromString($this->organizationId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function boardType(): string
    {
        return $this->boardType;
    }

    public function processDefinitionId(): ?string
    {
        return $this->processDefinitionId;
    }

    public function createdAt(): CreatedAt
    {
        return new CreatedAt($this->createdAt);
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
