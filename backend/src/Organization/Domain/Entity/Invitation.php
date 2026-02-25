<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\InvitationAcceptedEvent;
use App\Organization\Domain\Event\InvitationCancelledEvent;
use App\Organization\Domain\Event\InvitationCreatedEvent;
use App\Organization\Domain\Exception\InvitationExpiredException;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeNumber;
use App\Organization\Domain\ValueObject\InvitationId;
use App\Organization\Domain\ValueObject\InvitationStatus;
use App\Organization\Domain\ValueObject\InvitationToken;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\Email;

class Invitation extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private string $email;
    private string $departmentId;
    private string $positionId;
    private string $employeeNumber;
    private string $token;
    private string $status;
    private string $invitedByUserId;
    private \DateTimeImmutable $expiresAt;
    private ?\DateTimeImmutable $acceptedAt;
    private \DateTimeImmutable $createdAt;

    private function __construct()
    {
    }

    public static function create(
        InvitationId $id,
        OrganizationId $organizationId,
        Email $email,
        DepartmentId $departmentId,
        PositionId $positionId,
        EmployeeNumber $employeeNumber,
        InvitationToken $token,
        string $invitedByUserId,
        \DateTimeImmutable $expiresAt,
    ): self {
        $invitation = new self();
        $invitation->id = $id->value();
        $invitation->organizationId = $organizationId->value();
        $invitation->email = $email->value();
        $invitation->departmentId = $departmentId->value();
        $invitation->positionId = $positionId->value();
        $invitation->employeeNumber = $employeeNumber->value();
        $invitation->token = $token->value();
        $invitation->status = InvitationStatus::Pending->value;
        $invitation->invitedByUserId = $invitedByUserId;
        $invitation->expiresAt = $expiresAt;
        $invitation->acceptedAt = null;
        $invitation->createdAt = new \DateTimeImmutable();

        $invitation->recordEvent(new InvitationCreatedEvent(
            $id->value(),
            $organizationId->value(),
            $email->value(),
            $invitedByUserId,
        ));

        return $invitation;
    }

    public function accept(): void
    {
        if ($this->isExpired()) {
            $this->status = InvitationStatus::Expired->value;
            throw InvitationExpiredException::withId($this->id);
        }

        $this->status = InvitationStatus::Accepted->value;
        $this->acceptedAt = new \DateTimeImmutable();

        $this->recordEvent(new InvitationAcceptedEvent(
            $this->id,
            $this->organizationId,
            $this->email,
        ));
    }

    public function cancel(): void
    {
        $this->status = InvitationStatus::Cancelled->value;

        $this->recordEvent(new InvitationCancelledEvent(
            $this->id,
            $this->organizationId,
        ));
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return InvitationStatus::Pending->value === $this->status;
    }

    public function isAccepted(): bool
    {
        return InvitationStatus::Accepted->value === $this->status;
    }

    public function id(): InvitationId
    {
        return InvitationId::fromString($this->id);
    }

    public function organizationId(): OrganizationId
    {
        return OrganizationId::fromString($this->organizationId);
    }

    public function email(): Email
    {
        return new Email($this->email);
    }

    public function departmentId(): DepartmentId
    {
        return DepartmentId::fromString($this->departmentId);
    }

    public function positionId(): PositionId
    {
        return PositionId::fromString($this->positionId);
    }

    public function employeeNumber(): EmployeeNumber
    {
        return new EmployeeNumber($this->employeeNumber);
    }

    public function token(): InvitationToken
    {
        return new InvitationToken($this->token);
    }

    public function status(): InvitationStatus
    {
        return InvitationStatus::from($this->status);
    }

    public function invitedByUserId(): string
    {
        return $this->invitedByUserId;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function acceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
