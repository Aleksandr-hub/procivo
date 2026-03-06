<?php

declare(strict_types=1);

namespace App\Notification\Application\DTO;

use App\Notification\Domain\Entity\Notification;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'User notification')]
final readonly class NotificationDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'Notification UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Recipient user UUID', format: 'uuid')]
        public string $recipientId,
        #[OA\Property(description: 'Notification type', enum: ['task_assigned', 'task_completed', 'process_completed', 'invitation_created', 'process_started'])]
        public string $type,
        #[OA\Property(description: 'Notification title')]
        public string $title,
        #[OA\Property(description: 'Notification body text')]
        public string $body,
        #[OA\Property(description: 'Related entity UUID', format: 'uuid', nullable: true)]
        public ?string $relatedEntityId,
        #[OA\Property(description: 'Related entity type', nullable: true, example: 'task')]
        public ?string $relatedEntityType,
        #[OA\Property(description: 'Delivery channel', enum: ['in_app', 'email'])]
        public string $channel,
        #[OA\Property(description: 'Whether notification has been read')]
        public bool $isRead,
        #[OA\Property(description: 'Read timestamp', format: 'date-time', nullable: true)]
        public ?string $readAt,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
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
            relatedEntityType: $notification->relatedEntityType(),
            channel: $notification->channel(),
            isRead: $notification->isRead(),
            readAt: $notification->readAt()?->format(\DateTimeInterface::ATOM),
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
            'relatedEntityType' => $this->relatedEntityType,
            'channel' => $this->channel,
            'isRead' => $this->isRead,
            'readAt' => $this->readAt,
            'createdAt' => $this->createdAt,
        ];
    }
}
