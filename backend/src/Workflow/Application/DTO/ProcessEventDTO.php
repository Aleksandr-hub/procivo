<?php

declare(strict_types=1);

namespace App\Workflow\Application\DTO;

final readonly class ProcessEventDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $eventType,
        public array $payload,
        public int $version,
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
