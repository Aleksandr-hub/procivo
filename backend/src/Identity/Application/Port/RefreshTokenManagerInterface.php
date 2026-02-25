<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

interface RefreshTokenManagerInterface
{
    public function generate(string $userId, ?string $ip = null, ?string $userAgent = null): string;

    /**
     * @return array{user_id: string, ip: ?string, user_agent: ?string, created_at: string}|null
     */
    public function validate(string $token): ?array;

    public function revoke(string $token): void;

    public function rotate(string $oldToken, ?string $ip = null, ?string $userAgent = null): ?string;
}
