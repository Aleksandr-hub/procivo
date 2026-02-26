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
    private bool $isRead;
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
    ): self {
        $notification = new self();
        $notification->id = $id->value();
        $notification->recipientId = $recipientId;
        $notification->type = $type->value;
        $notification->title = $title;
        $notification->body = $body;
        $notification->relatedEntityId = $relatedEntityId;
        $notification->isRead = false;
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

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
    }
}
