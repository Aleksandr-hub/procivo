<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\PositionCreatedEvent;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use App\Organization\Domain\ValueObject\PositionName;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;

class Position extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private string $departmentId;
    private string $name;
    private ?string $description;
    private int $sortOrder;
    private bool $isHead;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        PositionId $id,
        OrganizationId $organizationId,
        DepartmentId $departmentId,
        PositionName $name,
        ?string $description,
        int $sortOrder,
        bool $isHead,
    ): self {
        $position = new self();
        $position->id = $id->value();
        $position->organizationId = $organizationId->value();
        $position->departmentId = $departmentId->value();
        $position->name = $name->value();
        $position->description = $description;
        $position->sortOrder = $sortOrder;
        $position->isHead = $isHead;
        $position->createdAt = new \DateTimeImmutable();
        $position->updatedAt = null;

        $position->recordEvent(new PositionCreatedEvent(
            $id->value(),
            $organizationId->value(),
            $departmentId->value(),
            $name->value(),
        ));

        return $position;
    }

    public function update(PositionName $name, ?string $description, int $sortOrder, bool $isHead): void
    {
        $this->name = $name->value();
        $this->description = $description;
        $this->sortOrder = $sortOrder;
        $this->isHead = $isHead;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): PositionId
    {
        return PositionId::fromString($this->id);
    }

    public function organizationId(): OrganizationId
    {
        return OrganizationId::fromString($this->organizationId);
    }

    public function departmentId(): DepartmentId
    {
        return DepartmentId::fromString($this->departmentId);
    }

    public function name(): PositionName
    {
        return new PositionName($this->name);
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isHead(): bool
    {
        return $this->isHead;
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
