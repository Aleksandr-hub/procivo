<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\OrganizationCreatedEvent;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationName;
use App\Organization\Domain\ValueObject\OrganizationSlug;
use App\Organization\Domain\ValueObject\OrganizationStatus;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;

class Organization extends AggregateRoot
{
    private string $id;
    private string $name;
    private string $slug;
    private ?string $description;
    private string $status;
    private string $ownerUserId;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        OrganizationId $id,
        OrganizationName $name,
        OrganizationSlug $slug,
        ?string $description,
        string $ownerUserId,
    ): self {
        $org = new self();
        $org->id = $id->value();
        $org->name = $name->value();
        $org->slug = $slug->value();
        $org->description = $description;
        $org->status = OrganizationStatus::Active->value;
        $org->ownerUserId = $ownerUserId;
        $org->createdAt = new \DateTimeImmutable();
        $org->updatedAt = null;

        $org->recordEvent(new OrganizationCreatedEvent($id->value(), $name->value(), $ownerUserId));

        return $org;
    }

    public function update(OrganizationName $name, ?string $description): void
    {
        $this->name = $name->value();
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function suspend(): void
    {
        $this->status = OrganizationStatus::Suspended->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->status = OrganizationStatus::Active->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): OrganizationId
    {
        return OrganizationId::fromString($this->id);
    }

    public function name(): OrganizationName
    {
        return new OrganizationName($this->name);
    }

    public function slug(): OrganizationSlug
    {
        return new OrganizationSlug($this->slug);
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function status(): OrganizationStatus
    {
        return OrganizationStatus::from($this->status);
    }

    public function ownerUserId(): string
    {
        return $this->ownerUserId;
    }

    public function createdAt(): CreatedAt
    {
        return new CreatedAt($this->createdAt);
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOwner(string $userId): bool
    {
        return $this->ownerUserId === $userId;
    }

    public function isActive(): bool
    {
        return OrganizationStatus::Active->value === $this->status;
    }

    public function isSuspended(): bool
    {
        return OrganizationStatus::Suspended->value === $this->status;
    }
}
