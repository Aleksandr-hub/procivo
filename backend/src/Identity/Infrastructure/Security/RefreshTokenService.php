<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\RefreshTokenManagerInterface;

final class RefreshTokenService implements RefreshTokenManagerInterface
{
    private const string PREFIX = 'refresh_token:';
    private const string USER_TOKENS_PREFIX = 'user_refresh_tokens:';
    private const int TTL = 2_592_000; // 30 days in seconds

    public function __construct(
        private \Redis $redis,
    ) {
    }

    public function generate(string $userId, ?string $ip = null, ?string $userAgent = null): string
    {
        $token = bin2hex(random_bytes(64));

        $payload = json_encode([
            'user_id' => $userId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], \JSON_THROW_ON_ERROR);

        $this->redis->setex(self::PREFIX.$token, self::TTL, $payload);
        $this->redis->sAdd(self::USER_TOKENS_PREFIX.$userId, $token);

        return $token;
    }

    /**
     * @return array{user_id: string, ip: ?string, user_agent: ?string, created_at: string}|null
     */
    public function validate(string $token): ?array
    {
        $payload = $this->redis->get(self::PREFIX.$token);

        if (false === $payload) {
            return null;
        }

        /* @var array{user_id: string, ip: ?string, user_agent: ?string, created_at: string} */
        return json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
    }

    public function revoke(string $token): void
    {
        $payload = $this->validate($token);

        if (null !== $payload) {
            $this->redis->sRem(self::USER_TOKENS_PREFIX.$payload['user_id'], $token);
        }

        $this->redis->del(self::PREFIX.$token);
    }

    public function revokeAllForUser(string $userId): void
    {
        $tokens = $this->redis->sMembers(self::USER_TOKENS_PREFIX.$userId);

        foreach ($tokens as $token) {
            $this->redis->del(self::PREFIX.$token);
        }

        $this->redis->del(self::USER_TOKENS_PREFIX.$userId);
    }

    /**
     * Rotate: revoke old token and generate a new one.
     */
    public function rotate(string $oldToken, ?string $ip = null, ?string $userAgent = null): ?string
    {
        $payload = $this->validate($oldToken);

        if (null === $payload) {
            return null;
        }

        $this->revoke($oldToken);

        return $this->generate($payload['user_id'], $ip, $userAgent);
    }
}
