<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Event\PasswordChangedEvent;
use App\Identity\Domain\Event\UserActivatedEvent;
use App\Identity\Domain\Event\UserRegisteredEvent;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use App\Identity\Domain\ValueObject\UserStatus;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\Email;

class User extends AggregateRoot
{
    /** @var string Doctrine-mapped field */
    private string $id;
    /** @var string Doctrine-mapped field */
    private string $email;
    /** @var string Doctrine-mapped field */
    private string $password;
    private string $firstName;
    private string $lastName;
    /** @var string Doctrine-mapped field (enum value) */
    private string $status;
    /** @var list<string> */
    private array $roles;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private ?string $avatarPath = null;

    private function __construct()
    {
    }

    public static function register(
        UserId $id,
        Email $email,
        HashedPassword $password,
        string $firstName,
        string $lastName,
    ): self {
        $user = new self();
        $user->id = $id->value();
        $user->email = $email->value();
        $user->password = $password->value();
        $user->firstName = $firstName;
        $user->lastName = $lastName;
        $user->status = UserStatus::Pending->value;
        $user->roles = ['ROLE_USER'];
        $user->createdAt = new \DateTimeImmutable();
        $user->updatedAt = null;
        $user->avatarPath = null;

        $user->recordEvent(new UserRegisteredEvent($id->value(), $email->value()));

        return $user;
    }

    public function activate(): void
    {
        $this->status = UserStatus::Active->value;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new UserActivatedEvent($this->id));
    }

    public function block(): void
    {
        $this->status = UserStatus::Blocked->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changePassword(HashedPassword $newPassword): void
    {
        $this->password = $newPassword->value();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new PasswordChangedEvent($this->id));
    }

    public function updateProfile(string $firstName, string $lastName, Email $email): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email->value();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setAvatarPath(?string $path): void
    {
        $this->avatarPath = $path;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function avatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function id(): UserId
    {
        return UserId::fromString($this->id);
    }

    public function email(): Email
    {
        return new Email($this->email);
    }

    public function password(): HashedPassword
    {
        return new HashedPassword($this->password);
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function status(): UserStatus
    {
        return UserStatus::from($this->status);
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function createdAt(): CreatedAt
    {
        return new CreatedAt($this->createdAt);
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return UserStatus::Active->value === $this->status;
    }

    public function isPending(): bool
    {
        return UserStatus::Pending->value === $this->status;
    }

    public function isBlocked(): bool
    {
        return UserStatus::Blocked->value === $this->status;
    }
}
