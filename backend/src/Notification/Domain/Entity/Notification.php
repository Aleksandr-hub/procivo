<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use App\Notification\Domain\ValueObject\NotificationId;
use App\Notification\Domain\ValueObject\NotificationType;

class Notification
{
    private string $id;
    private string $recipientId;
    private string $type;
    private string $title;
    private string $body;
    private ?string $relatedEntityId;
    private ?string $relatedEntityType;
    private string $channel;
    private bool $isRead;
    private ?\DateTimeImmutable $readAt;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        NotificationId $id,
        string $recipientId,
        NotificationType $type,
        string $title,
        string $body,
        ?string $relatedEntityId = null,
        string $channel = 'in_app',
        ?string $relatedEntityType = null,
    ): self {
        $notification = new self();
        $notification->id = $id->value();
        $notification->recipientId = $recipientId;
        $notification->type = $type->value;
        $notification->title = $title;
        $notification->body = $body;
        $notification->relatedEntityId = $relatedEntityId;
        $notification->relatedEntityType = $relatedEntityType;
        $notification->channel = $channel;
        $notification->isRead = false;
        $notification->readAt = null;
        $notification->createdAt = new \DateTimeImmutable();

        return $notification;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function recipientId(): string
    {
        return $this->recipientId;
    }

    public function type(): NotificationType
    {
        return NotificationType::from($this->type);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function relatedEntityId(): ?string
    {
        return $this->relatedEntityId;
    }

    public function relatedEntityType(): ?string
    {
        return $this->relatedEntityType;
    }

    public function channel(): string
    {
        return $this->channel;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function readAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();
    }
}
