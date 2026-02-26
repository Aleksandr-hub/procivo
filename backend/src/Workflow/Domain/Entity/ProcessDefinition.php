<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

use App\Shared\Domain\AggregateRoot;
use App\Workflow\Domain\Event\ProcessDefinitionCreatedEvent;
use App\Workflow\Domain\Event\ProcessDefinitionPublishedEvent;
use App\Workflow\Domain\Event\ProcessDefinitionRevertedToDraftEvent;
use App\Workflow\Domain\ValueObject\ProcessDefinitionId;
use App\Workflow\Domain\ValueObject\ProcessDefinitionStatus;

class ProcessDefinition extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private string $name;
    private ?string $description;
    private string $status;
    private string $createdBy;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function create(
        ProcessDefinitionId $id,
        string $organizationId,
        string $name,
        ?string $description,
        string $createdBy,
    ): self {
        $def = new self();
        $def->id = $id->value();
        $def->organizationId = $organizationId;
        $def->name = $name;
        $def->description = $description;
        $def->status = ProcessDefinitionStatus::Draft->value;
        $def->createdBy = $createdBy;
        $def->createdAt = new \DateTimeImmutable();
        $def->updatedAt = null;

        $def->recordEvent(new ProcessDefinitionCreatedEvent(
            $id->value(),
            $organizationId,
            $name,
            $createdBy,
        ));

        return $def;
    }

    public function update(string $name, ?string $description): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function publish(string $versionId, int $versionNumber, string $publishedBy): void
    {
        if (!$this->isDraft()) {
            throw new \DomainException('Only draft definitions can be published.');
        }

        $this->status = ProcessDefinitionStatus::Published->value;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new ProcessDefinitionPublishedEvent(
            $this->id,
            $versionId,
            $versionNumber,
            $publishedBy,
        ));
    }

    public function revertToDraft(string $revertedBy): void
    {
        if (!$this->isPublished()) {
            throw new \DomainException('Only published definitions can be reverted to draft.');
        }

        $this->status = ProcessDefinitionStatus::Draft->value;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new ProcessDefinitionRevertedToDraftEvent(
            $this->id,
            $revertedBy,
        ));
    }

    public function archive(): void
    {
        if ($this->isDraft()) {
            throw new \DomainException('Draft definitions must be published before archiving.');
        }

        $this->status = ProcessDefinitionStatus::Archived->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): ProcessDefinitionId
    {
        return ProcessDefinitionId::fromString($this->id);
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function status(): ProcessDefinitionStatus
    {
        return ProcessDefinitionStatus::from($this->status);
    }

    public function createdBy(): string
    {
        return $this->createdBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isDraft(): bool
    {
        return ProcessDefinitionStatus::Draft === $this->status();
    }

    public function isPublished(): bool
    {
        return ProcessDefinitionStatus::Published === $this->status();
    }
}
