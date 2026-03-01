<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Mercure;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final readonly class NotificationMercurePublisher
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function publishNotification(string $recipientId, array $data): void
    {
        $update = new Update(
            \sprintf('/users/%s/notifications', $recipientId),
            (string) json_encode([
                'event' => 'notification.created',
                'data' => $data,
            ]),
        );

        $this->hub->publish($update);
    }
}
