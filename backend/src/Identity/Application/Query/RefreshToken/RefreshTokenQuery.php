<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\RefreshToken;

use App\Shared\Application\Query\QueryInterface;

final readonly class RefreshTokenQuery implements QueryInterface
{
    public function __construct(
        public string $refreshToken,
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {
    }
}
