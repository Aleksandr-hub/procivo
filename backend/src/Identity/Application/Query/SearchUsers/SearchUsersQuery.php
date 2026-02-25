<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\SearchUsers;

use App\Shared\Application\Query\QueryInterface;

final readonly class SearchUsersQuery implements QueryInterface
{
    public function __construct(
        public string $search = '',
        public int $limit = 20,
    ) {
    }
}
