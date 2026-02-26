<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotifications;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListNotificationsQuery implements QueryInterface
{
    public function __construct(
        public string $recipientId,
        public int $limit = 50,
        public int $offset = 0,
    ) {
    }
}
