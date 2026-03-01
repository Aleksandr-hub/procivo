<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\UpdateProfile;

use App\Identity\Domain\Exception\UserAlreadyExistsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateProfileHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(UpdateProfileCommand $command): void
    {
        $user = $this->userRepository->findById(UserId::fromString($command->userId));

        if (null === $user) {
            throw new DomainException(\sprintf('User "%s" not found.', $command->userId));
        }

        $newEmail = new Email($command->email);

        // Check email uniqueness only when email is being changed
        if ($newEmail->value() !== $user->email()->value()) {
            if ($this->userRepository->existsByEmail($newEmail)) {
                throw UserAlreadyExistsException::withEmail($newEmail->value());
            }
        }

        $user->updateProfile($command->firstName, $command->lastName, $newEmail);

        $this->userRepository->save($user);
    }
}
