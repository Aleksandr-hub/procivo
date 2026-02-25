<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetInvitationByToken;

use App\Organization\Application\DTO\InvitationDTO;
use App\Organization\Domain\Exception\InvitationNotFoundException;
use App\Organization\Domain\Repository\InvitationRepositoryInterface;
use App\Organization\Domain\ValueObject\InvitationToken;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetInvitationByTokenHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {
    }

    public function __invoke(GetInvitationByTokenQuery $query): InvitationDTO
    {
        $invitation = $this->invitationRepository->findByToken(
            new InvitationToken($query->token),
        );

        if (null === $invitation) {
            throw InvitationNotFoundException::withToken($query->token);
        }

        return InvitationDTO::fromEntity($invitation);
    }
}
