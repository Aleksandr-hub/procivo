<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Entity;

use App\Workflow\Domain\ValueObject\NodeId;
use App\Workflow\Domain\ValueObject\TokenId;
use App\Workflow\Domain\ValueObject\TokenStatus;

final class Token
{
    private function __construct(
        private readonly TokenId $id,
        private NodeId $nodeId,
        private TokenStatus $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(TokenId $id, NodeId $nodeId): self
    {
        return new self($id, $nodeId, TokenStatus::Active, new \DateTimeImmutable());
    }

    public function moveTo(NodeId $nodeId): void
    {
        $this->nodeId = $nodeId;
        $this->status = TokenStatus::Active;
    }

    public function wait(): void
    {
        $this->status = TokenStatus::Waiting;
    }

    public function activate(): void
    {
        $this->status = TokenStatus::Active;
    }

    public function complete(): void
    {
        $this->status = TokenStatus::Completed;
    }

    public function cancel(): void
    {
        $this->status = TokenStatus::Cancelled;
    }

    public function id(): TokenId
    {
        return $this->id;
    }

    public function nodeId(): NodeId
    {
        return $this->nodeId;
    }

    public function status(): TokenStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return TokenStatus::Active === $this->status;
    }

    public function isWaiting(): bool
    {
        return TokenStatus::Waiting === $this->status;
    }

    public function isCompleted(): bool
    {
        return TokenStatus::Completed === $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
