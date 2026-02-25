<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\RoleCreatedEvent;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\RoleId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;

class Role extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private string $name;
    private ?string $description;
    private bool $isSystem;
    private int $hierarchy;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        RoleId $id,
        OrganizationId $organizationId,
        string $name,
        ?string $description,
        bool $isSystem,
        int $hierarchy,
    ): self {
        $role = new self();
        $role->id = $id->value();
        $role->organizationId = $organizationId->value();
        $role->name = $name;
        $role->description = $description;
        $role->isSystem = $isSystem;
        $role->hierarchy = $hierarchy;
        $role->createdAt = new \DateTimeImmutable();
        $role->updatedAt = null;

        $role->recordEvent(new RoleCreatedEvent(
            $id->value(),
            $organizationId->value(),
            $name,
        ));

        return $role;
    }

    public function update(string $name, ?string $description, int $hierarchy): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->hierarchy = $hierarchy;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): RoleId
    {
        return RoleId::fromString($this->id);
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

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function hierarchy(): int
    {
        return $this->hierarchy;
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
