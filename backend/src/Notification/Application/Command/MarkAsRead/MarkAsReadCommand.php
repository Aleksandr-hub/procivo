<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkAsRead;

use App\Shared\Application\Command\CommandInterface;

final readonly class MarkAsReadCommand implements CommandInterface
{
    public function __construct(
        public string $notificationId,
        public string $recipientId,
    ) {
    }
}
