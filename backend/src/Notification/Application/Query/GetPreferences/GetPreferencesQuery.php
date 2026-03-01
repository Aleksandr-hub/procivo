<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\GetPreferences;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetPreferencesQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
    ) {
    }
}
