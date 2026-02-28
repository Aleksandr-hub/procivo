<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class NotificationNodeActivatedEvent implements DomainEvent
{
    /**
     * @param array<string, mixed> $notificationConfig
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $processInstanceId,
        public string $organizationId,
        public string $nodeId,
        public string $tokenId,
        public array $notificationConfig,
        public array $variables,
        public string $startedBy,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.notification_node.activated';
    }
}
