<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkAllAsRead;

use App\Shared\Application\Command\CommandInterface;

final readonly class MarkAllAsReadCommand implements CommandInterface
{
    public function __construct(
        public string $recipientId,
    ) {
    }
}
