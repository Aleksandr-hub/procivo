<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

interface JwtTokenManagerInterface
{
    /**
     * @param list<string> $roles
     */
    public function create(string $userId, string $email, array $roles): string;

    /**
     * Create a short-lived impersonation JWT with impersonated_by claim.
     *
     * @param list<string> $roles
     */
    public function createImpersonation(
        string $userId,
        string $email,
        array $roles,
        string $impersonatedBy,
        int $ttl = 900,
    ): string;
}
