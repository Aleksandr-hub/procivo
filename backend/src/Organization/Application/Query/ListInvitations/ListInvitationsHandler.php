<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListInvitations;

use App\Organization\Application\DTO\InvitationDTO;
use App\Organization\Domain\Repository\InvitationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListInvitationsHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {
    }

    /**
     * @return list<InvitationDTO>
     */
    public function __invoke(ListInvitationsQuery $query): array
    {
        $invitations = $this->invitationRepository->findByOrganizationId(
            OrganizationId::fromString($query->organizationId),
        );

        return array_map(InvitationDTO::fromEntity(...), $invitations);
    }
}
