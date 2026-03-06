<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use App\Identity\Domain\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'User profile resource')]
final readonly class UserDTO
{
    public function __construct(
        #[OA\Property(description: 'User UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'User email address', format: 'email')]
        public string $email,
        #[OA\Property(description: 'First name')]
        public string $firstName,
        #[OA\Property(description: 'Last name')]
        public string $lastName,
        #[OA\Property(description: 'Account status', enum: ['active', 'inactive', 'banned'])]
        public string $status,
        /** @var list<string> */
        #[OA\Property(description: 'Assigned roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER'))]
        public array $roles,
        #[OA\Property(description: 'Account creation timestamp', format: 'date-time')]
        public string $createdAt,
        #[OA\Property(description: 'Avatar image URL', format: 'uri', nullable: true)]
        public ?string $avatarUrl = null,
        #[OA\Property(description: 'Whether TOTP two-factor authentication is enabled')]
        public bool $totpEnabled = false,
    ) {
    }

    public static function fromEntity(User $user, ?string $avatarUrl = null): self
    {
        return new self(
            id: $user->id()->value(),
            email: $user->email()->value(),
            firstName: $user->firstName(),
            lastName: $user->lastName(),
            status: $user->status()->value,
            roles: $user->roles(),
            createdAt: (string) $user->createdAt(),
            avatarUrl: $avatarUrl,
            totpEnabled: $user->isTotpEnabled(),
        );
    }
}
