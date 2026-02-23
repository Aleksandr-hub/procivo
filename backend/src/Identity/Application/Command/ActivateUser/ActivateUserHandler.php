<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ActivateUser;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ActivateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(ActivateUserCommand $command): void
    {
        $user = $this->userRepository->findById(UserId::fromString($command->userId));

        if (null === $user) {
            throw new DomainException(\sprintf('User "%s" not found.', $command->userId));
        }

        $user->activate();

        $this->userRepository->save($user);
    }
}
