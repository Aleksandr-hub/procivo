<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\JwtTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface as LexikJWTManager;

final readonly class LexikJwtTokenManager implements JwtTokenManagerInterface
{
    public function __construct(
        private LexikJWTManager $jwtManager,
        private JWTEncoderInterface $jwtEncoder,
    ) {
    }

    public function create(string $userId, string $email, array $roles): string
    {
        $securityUser = new SecurityUser(
            id: $userId,
            email: $email,
            password: '',
            roles: $roles,
        );

        return $this->jwtManager->create($securityUser);
    }

    public function createImpersonation(
        string $userId,
        string $email,
        array $roles,
        string $impersonatedBy,
        int $ttl = 900,
    ): string {
        $now = time();

        return $this->jwtEncoder->encode([
            'user_id' => $userId,
            'username' => $email,
            'roles' => $roles,
            'impersonated_by' => $impersonatedBy,
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);
    }

    public function createPartial(string $userId, string $email, int $ttl = 300): string
    {
        $now = time();

        return $this->jwtEncoder->encode([
            'user_id' => $userId,
            'username' => $email,
            'roles' => [],
            '2fa_required' => true,
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);
    }
}
