<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\InviteUser;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Organization\Application\Port\InvitationMailerInterface;
use App\Organization\Domain\Entity\Invitation;
use App\Organization\Domain\Exception\InvitationAlreadyExistsException;
use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Repository\InvitationRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeNumber;
use App\Organization\Domain\ValueObject\InvitationId;
use App\Organization\Domain\ValueObject\InvitationToken;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class InviteUserHandler
{
    private const int EXPIRATION_DAYS = 7;

    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private InvitationRepositoryInterface $invitationRepository,
        private UserRepositoryInterface $userRepository,
        private InvitationMailerInterface $invitationMailer,
    ) {
    }

    public function __invoke(InviteUserCommand $command): void
    {
        $organizationId = OrganizationId::fromString($command->organizationId);
        $email = new Email($command->email);

        $organization = $this->organizationRepository->findById($organizationId);
        if (null === $organization) {
            throw OrganizationNotFoundException::withId($command->organizationId);
        }

        $existing = $this->invitationRepository->findPendingByEmailAndOrganization($email, $organizationId);
        if (null !== $existing) {
            throw InvitationAlreadyExistsException::forEmail($command->email, $command->organizationId);
        }

        $token = InvitationToken::generate();
        $expiresAt = new \DateTimeImmutable(\sprintf('+%d days', self::EXPIRATION_DAYS));

        $invitation = Invitation::create(
            id: InvitationId::fromString($command->id),
            organizationId: $organizationId,
            email: $email,
            departmentId: DepartmentId::fromString($command->departmentId),
            positionId: PositionId::fromString($command->positionId),
            employeeNumber: new EmployeeNumber($command->employeeNumber),
            token: $token,
            invitedByUserId: $command->invitedByUserId,
            expiresAt: $expiresAt,
        );

        $this->invitationRepository->save($invitation);

        $inviterName = $this->resolveInviterName($command->invitedByUserId);

        $this->invitationMailer->sendInvitation(
            recipientEmail: $command->email,
            organizationName: $organization->name()->value(),
            inviterName: $inviterName,
            token: $token->value(),
            expiresAt: $expiresAt,
        );
    }

    private function resolveInviterName(string $userId): string
    {
        $user = $this->userRepository->findById(UserId::fromString($userId));
        if (null === $user) {
            return 'Unknown';
        }

        return $user->firstName() . ' ' . $user->lastName();
    }
}
