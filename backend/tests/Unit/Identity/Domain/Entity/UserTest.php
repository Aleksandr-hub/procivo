<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Event\PasswordChangedEvent;
use App\Identity\Domain\Event\UserActivatedEvent;
use App\Identity\Domain\Event\UserRegisteredEvent;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use App\Identity\Domain\ValueObject\UserStatus;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[Test]
    public function itRegistersANewUser(): void
    {
        $user = $this->createUser();

        self::assertNotEmpty($user->id()->value());
        self::assertSame('john@example.com', $user->email()->value());
        self::assertSame('John', $user->firstName());
        self::assertSame('Doe', $user->lastName());
        self::assertSame(UserStatus::Pending, $user->status());
        self::assertTrue($user->isPending());
        self::assertFalse($user->isActive());
        self::assertContains('ROLE_USER', $user->roles());
        self::assertNull($user->updatedAt());
    }

    #[Test]
    public function itRecordsUserRegisteredEvent(): void
    {
        $user = $this->createUser();

        $events = $user->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegisteredEvent::class, $events[0]);
        self::assertSame('john@example.com', $events[0]->email);
    }

    #[Test]
    public function itActivatesUser(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents();

        $user->activate();

        self::assertSame(UserStatus::Active, $user->status());
        self::assertTrue($user->isActive());
        self::assertNotNull($user->updatedAt());

        $events = $user->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserActivatedEvent::class, $events[0]);
    }

    #[Test]
    public function itBlocksUser(): void
    {
        $user = $this->createUser();

        $user->block();

        self::assertSame(UserStatus::Blocked, $user->status());
        self::assertTrue($user->isBlocked());
        self::assertFalse($user->isActive());
    }

    #[Test]
    public function itChangesPassword(): void
    {
        $user = $this->createUser();
        $user->pullDomainEvents();

        $newPassword = new HashedPassword('new-hashed-password');
        $user->changePassword($newPassword);

        self::assertSame('new-hashed-password', $user->password()->value());
        self::assertNotNull($user->updatedAt());

        $events = $user->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PasswordChangedEvent::class, $events[0]);
    }

    private function createUser(): User
    {
        return User::register(
            id: UserId::generate(),
            email: new Email('john@example.com'),
            password: new HashedPassword('hashed-password-value'),
            firstName: 'John',
            lastName: 'Doe',
        );
    }
}
