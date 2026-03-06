<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Authentication tokens returned after successful login')]
final readonly class AuthTokensDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'JWT access token')]
        public string $accessToken,
        #[OA\Property(description: 'Refresh token for obtaining new access tokens')]
        public string $refreshToken,
        #[OA\Property(description: 'Token type', example: 'Bearer')]
        public string $tokenType = 'Bearer',
        #[OA\Property(description: 'Access token TTL in seconds', example: 3600)]
        public int $expiresIn = 3600,
    ) {
    }

    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
        ];
    }
}
