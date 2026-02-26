<?php

declare(strict_types=1);

namespace App\TaskManager\Infrastructure\Mercure;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final readonly class TaskMercurePublisher
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function publishTaskUpdate(string $organizationId, string $eventType, array $data): void
    {
        $update = new Update(
            \sprintf('/organizations/%s/tasks', $organizationId),
            (string) json_encode([
                'event' => $eventType,
                'data' => $data,
            ]),
        );

        $this->hub->publish($update);
    }
}
