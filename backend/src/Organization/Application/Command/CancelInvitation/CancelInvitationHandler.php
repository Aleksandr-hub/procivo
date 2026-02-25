<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CancelInvitation;

use App\Organization\Domain\Exception\InvitationNotFoundException;
use App\Organization\Domain\Repository\InvitationRepositoryInterface;
use App\Organization\Domain\ValueObject\InvitationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CancelInvitationHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
    ) {
    }

    public function __invoke(CancelInvitationCommand $command): void
    {
        $invitation = $this->invitationRepository->findById(
            InvitationId::fromString($command->invitationId),
        );

        if (null === $invitation) {
            throw InvitationNotFoundException::withId($command->invitationId);
        }

        $invitation->cancel();

        $this->invitationRepository->save($invitation);
    }
}
