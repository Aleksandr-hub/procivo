<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

class WorkflowTaskLink
{
    private string $id;
    private string $processInstanceId;
    private string $tokenId;
    private string $taskId;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $completedAt = null;

    private function __construct()
    {
    }

    public static function create(
        string $id,
        string $processInstanceId,
        string $tokenId,
        string $taskId,
    ): self {
        $link = new self();
        $link->id = $id;
        $link->processInstanceId = $processInstanceId;
        $link->tokenId = $tokenId;
        $link->taskId = $taskId;
        $link->createdAt = new \DateTimeImmutable();

        return $link;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function processInstanceId(): string
    {
        return $this->processInstanceId;
    }

    public function tokenId(): string
    {
        return $this->tokenId;
    }

    public function taskId(): string
    {
        return $this->taskId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markCompleted(): void
    {
        $this->completedAt = new \DateTimeImmutable();
    }

    public function isCompleted(): bool
    {
        return null !== $this->completedAt;
    }

    public function completedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }
}
