<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Process event from the event store')]
final readonly class ProcessEventDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        #[OA\Property(description: 'Event UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Event type', example: 'ProcessStarted')]
        public string $eventType,
        #[OA\Property(description: 'Event payload', type: 'object')]
        public array $payload,
        #[OA\Property(description: 'Event version (sequence number)')]
        public int $version,
        #[OA\Property(description: 'Event timestamp', format: 'date-time')]
        public string $occurredAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->eventType,
            'payload' => $this->payload,
            'version' => $this->version,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
