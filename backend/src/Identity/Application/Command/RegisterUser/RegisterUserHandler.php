<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RegisterUser;

use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Exception\UserAlreadyExistsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): string
    {
        $email = new Email($command->email);

        if ($this->userRepository->existsByEmail($email)) {
            throw UserAlreadyExistsException::withEmail($command->email);
        }

        $user = User::register(
            id: UserId::generate(),
            email: $email,
            password: new HashedPassword($this->passwordHasher->hash($command->password)),
            firstName: $command->firstName,
            lastName: $command->lastName,
        );

        $this->userRepository->save($user);

        return $user->id()->value();
    }
}
