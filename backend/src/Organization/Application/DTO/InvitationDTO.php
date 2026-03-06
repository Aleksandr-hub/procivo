<?php

declare(strict_types=1);

namespace App\Organization\Application\DTO;

use App\Organization\Domain\Entity\Invitation;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Organization membership invitation')]
final readonly class InvitationDTO
{
    public function __construct(
        #[OA\Property(description: 'Invitation UUID', format: 'uuid')]
        public string $id,
        #[OA\Property(description: 'Organization UUID', format: 'uuid')]
        public string $organizationId,
        #[OA\Property(description: 'Invitee email address', format: 'email')]
        public string $email,
        #[OA\Property(description: 'Target department UUID', format: 'uuid')]
        public string $departmentId,
        #[OA\Property(description: 'Target position UUID', format: 'uuid')]
        public string $positionId,
        #[OA\Property(description: 'Assigned employee number')]
        public string $employeeNumber,
        #[OA\Property(description: 'Invitation status', enum: ['pending', 'accepted', 'expired', 'cancelled'])]
        public string $status,
        #[OA\Property(description: 'Inviter user UUID', format: 'uuid')]
        public string $invitedByUserId,
        #[OA\Property(description: 'Expiration timestamp', format: 'date-time')]
        public string $expiresAt,
        #[OA\Property(description: 'Acceptance timestamp', format: 'date-time', nullable: true)]
        public ?string $acceptedAt,
        #[OA\Property(description: 'Creation timestamp', format: 'date-time')]
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
