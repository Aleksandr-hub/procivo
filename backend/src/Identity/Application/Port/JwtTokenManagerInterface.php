<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

interface JwtTokenManagerInterface
{
    /**
     * @param list<string> $roles
     */
    public function create(string $userId, string $email, array $roles): string;
}
