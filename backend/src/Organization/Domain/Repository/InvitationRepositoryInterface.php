<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repository;

use App\Organization\Domain\Entity\Invitation;
use App\Organization\Domain\ValueObject\InvitationId;
use App\Organization\Domain\ValueObject\InvitationToken;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\ValueObject\Email;

interface InvitationRepositoryInterface
{
    public function save(Invitation $invitation): void;

    public function findById(InvitationId $id): ?Invitation;

    public function findByToken(InvitationToken $token): ?Invitation;

    public function findPendingByEmailAndOrganization(Email $email, OrganizationId $organizationId): ?Invitation;

    /**
     * @return list<Invitation>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;
}
