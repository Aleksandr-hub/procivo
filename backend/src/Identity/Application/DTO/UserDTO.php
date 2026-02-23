<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use App\Identity\Domain\Entity\User;

final readonly class UserDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $status,
        /** @var list<string> */
        public array $roles,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            email: $user->email()->value(),
            firstName: $user->firstName(),
            lastName: $user->lastName(),
            status: $user->status()->value,
            roles: $user->roles(),
            createdAt: (string) $user->createdAt(),
        );
    }
}
