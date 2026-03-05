<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ImpersonateUser;

use App\Shared\Application\Query\QueryInterface;

final readonly class ImpersonateUserQuery implements QueryInterface
{
    public function __construct(
        public string $adminUserId,
        public string $targetUserId,
        public string $reason = '',
        public bool $isAlreadyImpersonating = false,
    ) {
    }
}
