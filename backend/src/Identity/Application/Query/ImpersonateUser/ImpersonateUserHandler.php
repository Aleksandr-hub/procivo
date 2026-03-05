<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ImpersonateUser;

use App\Identity\Application\DTO\ImpersonationDTO;
use App\Identity\Application\DTO\UserDTO;
use App\Identity\Application\Port\JwtTokenManagerInterface;
use App\Identity\Domain\Event\ImpersonationStartedEvent;
use App\Identity\Domain\Exception\ImpersonationNotAllowedException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Bus\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ImpersonateUserHandler
{
    private const int IMPERSONATION_TTL = 900; // 15 minutes

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private JwtTokenManagerInterface $jwtTokenManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(ImpersonateUserQuery $query): ImpersonationDTO
    {
        if ($query->isAlreadyImpersonating) {
            throw ImpersonationNotAllowedException::alreadyImpersonating();
        }

        $targetUser = $this->userRepository->findById(UserId::fromString($query->targetUserId));

        if (null === $targetUser || !$targetUser->isActive()) {
            throw ImpersonationNotAllowedException::userNotFound();
        }

        if (\in_array('ROLE_SUPER_ADMIN', $targetUser->roles(), true)) {
            throw ImpersonationNotAllowedException::cannotImpersonateSuperAdmin();
        }

        $accessToken = $this->jwtTokenManager->createImpersonation(
            userId: $targetUser->id()->value(),
            email: $targetUser->email()->value(),
            roles: $targetUser->roles(),
            impersonatedBy: $query->adminUserId,
            ttl: self::IMPERSONATION_TTL,
        );

        $this->eventBus->dispatch(new ImpersonationStartedEvent(
            adminUserId: $query->adminUserId,
            targetUserId: $query->targetUserId,
            reason: $query->reason,
        ));

        return new ImpersonationDTO(
            accessToken: $accessToken,
            impersonatedUser: UserDTO::fromEntity($targetUser),
            expiresIn: self::IMPERSONATION_TTL,
        );
    }
}
