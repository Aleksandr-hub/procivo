<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Impersonation session with temporary access token')]
final readonly class ImpersonationDTO implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(description: 'Temporary JWT access token for impersonation')]
        public string $accessToken,
        #[OA\Property(description: 'Impersonated user details')]
        public UserDTO $impersonatedUser,
        #[OA\Property(description: 'Token TTL in seconds', example: 900)]
        public int $expiresIn,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->accessToken,
            'impersonated_user' => [
                'id' => $this->impersonatedUser->id,
                'email' => $this->impersonatedUser->email,
                'firstName' => $this->impersonatedUser->firstName,
                'lastName' => $this->impersonatedUser->lastName,
            ],
            'expires_in' => $this->expiresIn,
        ];
    }
}
