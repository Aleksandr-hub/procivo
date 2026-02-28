<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class WebhookNodeActivatedEvent implements DomainEvent
{
    /**
     * @param array<string, mixed> $webhookConfig
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $processInstanceId,
        public string $organizationId,
        public string $nodeId,
        public string $tokenId,
        public array $webhookConfig,
        public array $variables,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'workflow.webhook_node.activated';
    }
}
