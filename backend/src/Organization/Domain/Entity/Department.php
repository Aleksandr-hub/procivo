<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\DepartmentCreatedEvent;
use App\Organization\Domain\Event\DepartmentMovedEvent;
use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPath;
use App\Organization\Domain\ValueObject\DepartmentStatus;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;

class Department extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private ?string $parentId;
    private string $name;
    private string $code;
    private ?string $description;
    private int $sortOrder;
    private int $level;
    private string $path;
    private string $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        DepartmentId $id,
        OrganizationId $organizationId,
        ?DepartmentId $parentId,
        string $name,
        DepartmentCode $code,
        ?string $description,
        int $sortOrder,
        int $level,
        DepartmentPath $path,
    ): self {
        $dept = new self();
        $dept->id = $id->value();
        $dept->organizationId = $organizationId->value();
        $dept->parentId = $parentId?->value();
        $dept->name = $name;
        $dept->code = $code->value();
        $dept->description = $description;
        $dept->sortOrder = $sortOrder;
        $dept->level = $level;
        $dept->path = $path->value();
        $dept->status = DepartmentStatus::Active->value;
        $dept->createdAt = new \DateTimeImmutable();
        $dept->updatedAt = null;

        $dept->recordEvent(new DepartmentCreatedEvent(
            $id->value(),
            $organizationId->value(),
            $name,
            $parentId?->value(),
        ));

        return $dept;
    }

    public function update(string $name, ?string $description, int $sortOrder): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->sortOrder = $sortOrder;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function moveTo(?DepartmentId $newParentId, int $newLevel, DepartmentPath $newPath): void
    {
        $oldParentId = $this->parentId;
        $this->parentId = $newParentId?->value();
        $this->level = $newLevel;
        $this->path = $newPath->value();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new DepartmentMovedEvent(
            $this->id,
            $this->organizationId,
            $oldParentId,
            $newParentId?->value(),
        ));
    }

    public function updatePath(int $newLevel, DepartmentPath $newPath): void
    {
        $this->level = $newLevel;
        $this->path = $newPath->value();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function archive(): void
    {
        $this->status = DepartmentStatus::Archived->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): DepartmentId
    {
        return DepartmentId::fromString($this->id);
    }

    public function organizationId(): OrganizationId
    {
        return OrganizationId::fromString($this->organizationId);
    }

    public function parentId(): ?DepartmentId
    {
        return null !== $this->parentId ? DepartmentId::fromString($this->parentId) : null;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function code(): DepartmentCode
    {
        return new DepartmentCode($this->code);
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function path(): DepartmentPath
    {
        return new DepartmentPath($this->path);
    }

    public function status(): DepartmentStatus
    {
        return DepartmentStatus::from($this->status);
    }

    public function createdAt(): CreatedAt
    {
        return new CreatedAt($this->createdAt);
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return DepartmentStatus::Active->value === $this->status;
    }

    public function isArchived(): bool
    {
        return DepartmentStatus::Archived->value === $this->status;
    }
}
