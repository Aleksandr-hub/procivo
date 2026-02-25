<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

final readonly class AuthTokensDTO implements \JsonSerializable
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType = 'Bearer',
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
