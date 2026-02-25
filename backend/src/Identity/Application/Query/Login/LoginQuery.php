<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\Login;

use App\Shared\Application\Query\QueryInterface;

final readonly class LoginQuery implements QueryInterface
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {
    }
}
