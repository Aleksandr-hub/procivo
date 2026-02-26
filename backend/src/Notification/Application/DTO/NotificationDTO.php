<?php

declare(strict_types=1);

namespace App\Notification\Application\DTO;

use App\Notification\Domain\Entity\Notification;

final readonly class NotificationDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $recipientId,
        public string $type,
        public string $title,
        public string $body,
        public ?string $relatedEntityId,
        public bool $isRead,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Notification $notification): self
    {
        return new self(
            id: $notification->id(),
            recipientId: $notification->recipientId(),
            type: $notification->type()->value,
            title: $notification->title(),
            body: $notification->body(),
            relatedEntityId: $notification->relatedEntityId(),
            isRead: $notification->isRead(),
            createdAt: $notification->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'recipientId' => $this->recipientId,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'relatedEntityId' => $this->relatedEntityId,
            'isRead' => $this->isRead,
            'createdAt' => $this->createdAt,
        ];
    }
}
