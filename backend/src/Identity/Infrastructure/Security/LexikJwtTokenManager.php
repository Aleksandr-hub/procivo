<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\JwtTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface as LexikJWTManager;

final readonly class LexikJwtTokenManager implements JwtTokenManagerInterface
{
    public function __construct(
        private LexikJWTManager $jwtManager,
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
}
