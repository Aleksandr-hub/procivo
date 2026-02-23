<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\Exception\UserAlreadyExistsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    #[Test]
    public function itRegistersANewUser(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repository->method('existsByEmail')->willReturn(false);
        $repository->expects(self::once())->method('save');

        $hasher->method('hash')->with('plain-password')->willReturn('hashed-password');

        $handler = new RegisterUserHandler($repository, $hasher);

        $userId = $handler(new RegisterUserCommand(
            email: 'john@example.com',
            password: 'plain-password',
            firstName: 'John',
            lastName: 'Doe',
        ));

        self::assertNotEmpty($userId);
    }

    #[Test]
    public function itThrowsWhenEmailAlreadyExists(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repository->method('existsByEmail')->willReturn(true);

        $handler = new RegisterUserHandler($repository, $hasher);

        $this->expectException(UserAlreadyExistsException::class);

        $handler(new RegisterUserCommand(
            email: 'john@example.com',
            password: 'plain-password',
            firstName: 'John',
            lastName: 'Doe',
        ));
    }
}
