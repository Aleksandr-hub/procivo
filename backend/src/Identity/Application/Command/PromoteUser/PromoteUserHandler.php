<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\PromoteUser;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class PromoteUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(PromoteUserCommand $command): void
    {
        $user = $this->userRepository->findByEmail(new Email($command->email));

        if (null === $user) {
            throw new DomainException(\sprintf('User with email "%s" not found.', $command->email));
        }

        $user->addRole($command->role);

        $this->userRepository->save($user);
    }
}
