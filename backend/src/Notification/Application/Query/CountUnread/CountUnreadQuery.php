<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\CountUnread;

use App\Shared\Application\Query\QueryInterface;

final readonly class CountUnreadQuery implements QueryInterface
{
    public function __construct(
        public string $recipientId,
    ) {
    }
}
