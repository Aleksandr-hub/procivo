<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

final readonly class ImpersonationDTO implements \JsonSerializable
{
    public function __construct(
        public string $accessToken,
        public UserDTO $impersonatedUser,
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
