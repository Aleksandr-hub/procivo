<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class RememberDeviceService
{
    private const int DEVICE_REMEMBER_DAYS = 30;

    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        private string $appSecret,
    ) {
    }

    public function createDeviceToken(string $userId, string $userAgent): string
    {
        $expiry = time() + (self::DEVICE_REMEMBER_DAYS * 86400);
        $uaHash = hash('sha256', $userAgent);
        $payload = $userId . '|' . $uaHash . '|' . $expiry;
        $signature = hash_hmac('sha256', $payload, $this->appSecret);

        return base64_encode($payload . '|' . $signature);
    }

    public function isDeviceRemembered(string $token, string $userId, string $userAgent): bool
    {
        $decoded = base64_decode($token, true);

        if (false === $decoded) {
            return false;
        }

        $parts = explode('|', $decoded);

        if (4 !== \count($parts)) {
            return false;
        }

        [$tokenUserId, $tokenUaHash, $tokenExpiry, $tokenSignature] = $parts;

        // Verify HMAC signature
        $payload = $tokenUserId . '|' . $tokenUaHash . '|' . $tokenExpiry;
        $expectedSignature = hash_hmac('sha256', $payload, $this->appSecret);

        if (!hash_equals($expectedSignature, $tokenSignature)) {
            return false;
        }

        // Check expiry
        if ((int) $tokenExpiry < time()) {
            return false;
        }

        // Match userId
        if ($tokenUserId !== $userId) {
            return false;
        }

        // Match user agent hash
        $expectedUaHash = hash('sha256', $userAgent);

        return hash_equals($expectedUaHash, $tokenUaHash);
    }
}
