<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\ChangePassword\ChangePasswordCommand;
use App\Identity\Application\Command\ChangePassword\ChangePasswordHandler;
use App\Identity\Application\Port\PasswordHasherInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Exception\InvalidCredentialsException;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChangePasswordHandlerTest extends TestCase
{
    #[Test]
    public function itChangesPassword(): void
    {
        $user = User::register(
            id: UserId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b'),
            email: new Email('john@example.com'),
            password: new HashedPassword('old-hash'),
            firstName: 'John',
            lastName: 'Doe',
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repository->method('findById')->willReturn($user);
        $repository->expects(self::once())->method('save');

        $hasher->method('verify')->with('old-hash', 'current-password')->willReturn(true);
        $hasher->method('hash')->with('new-password')->willReturn('new-hash');

        $handler = new ChangePasswordHandler($repository, $hasher);

        $handler(new ChangePasswordCommand(
            userId: '01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b',
            currentPassword: 'current-password',
            newPassword: 'new-password',
        ));

        self::assertSame('new-hash', $user->password()->value());
    }

    #[Test]
    public function itThrowsOnWrongCurrentPassword(): void
    {
        $user = User::register(
            id: UserId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b'),
            email: new Email('john@example.com'),
            password: new HashedPassword('old-hash'),
            firstName: 'John',
            lastName: 'Doe',
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repository->method('findById')->willReturn($user);

        $hasher->method('verify')->willReturn(false);

        $handler = new ChangePasswordHandler($repository, $hasher);

        $this->expectException(InvalidCredentialsException::class);

        $handler(new ChangePasswordCommand(
            userId: '01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b',
            currentPassword: 'wrong-password',
            newPassword: 'new-password',
        ));
    }
}
