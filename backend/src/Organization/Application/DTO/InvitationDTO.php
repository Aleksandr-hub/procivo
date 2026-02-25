<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Invitation;

final readonly class InvitationDTO
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $email,
        public string $departmentId,
        public string $positionId,
        public string $employeeNumber,
        public string $status,
        public string $invitedByUserId,
        public string $expiresAt,
        public ?string $acceptedAt,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Invitation $invitation): self
    {
        return new self(
            id: $invitation->id()->value(),
            organizationId: $invitation->organizationId()->value(),
            email: $invitation->email()->value(),
            departmentId: $invitation->departmentId()->value(),
            positionId: $invitation->positionId()->value(),
            employeeNumber: $invitation->employeeNumber()->value(),
            status: $invitation->status()->value,
            invitedByUserId: $invitation->invitedByUserId(),
            expiresAt: $invitation->expiresAt()->format(\DateTimeInterface::ATOM),
            acceptedAt: $invitation->acceptedAt()?->format(\DateTimeInterface::ATOM),
            createdAt: $invitation->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
