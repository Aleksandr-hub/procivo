<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AcceptInvitation;

use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use App\Organization\Domain\Entity\Employee;
use App\Organization\Domain\Exception\InvitationNotFoundException;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\InvitationRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\InvitationToken;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AcceptInvitationHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private UserRepositoryInterface $userRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(AcceptInvitationCommand $command): void
    {
        $invitation = $this->invitationRepository->findByToken(
            new InvitationToken($command->token),
        );

        if (null === $invitation || !$invitation->isPending()) {
            throw InvitationNotFoundException::withToken($command->token);
        }

        $invitation->accept();

        $user = $this->resolveUser(
            $invitation->email(),
            $command->firstName,
            $command->lastName,
            $command->password,
        );

        $employee = Employee::hire(
            id: EmployeeId::generate(),
            organizationId: $invitation->organizationId(),
            userId: $user->id()->value(),
            positionId: $invitation->positionId(),
            departmentId: $invitation->departmentId(),
            employeeNumber: $invitation->employeeNumber(),
            hiredAt: new \DateTimeImmutable(),
        );

        $this->invitationRepository->save($invitation);
        $this->employeeRepository->save($employee);
    }

    private function resolveUser(
        Email $email,
        string $firstName,
        string $lastName,
        string $plainPassword,
    ): User {
        $existing = $this->userRepository->findByEmail($email);

        if (null !== $existing) {
            return $existing;
        }

        $hashedPassword = new HashedPassword($this->passwordHasher->hash($plainPassword));

        $user = User::register(
            id: UserId::generate(),
            email: $email,
            password: $hashedPassword,
            firstName: $firstName,
            lastName: $lastName,
        );

        $user->activate();

        $this->userRepository->save($user);

        return $user;
    }
}
