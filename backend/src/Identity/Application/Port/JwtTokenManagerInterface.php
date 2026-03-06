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

    /**
     * Create a short-lived partial JWT for 2FA challenge.
     * Token contains 2fa_required claim and grants access only to /2fa/verify.
     */
    public function createPartial(string $userId, string $email, int $ttl = 300): string;
}
