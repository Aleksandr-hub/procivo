<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetInvitationByToken;

use App\Shared\Application\Query\QueryInterface;

final readonly class GetInvitationByTokenQuery implements QueryInterface
{
    public function __construct(
        public string $token,
    ) {
    }
}
