<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entity;

use App\Organization\Domain\Event\EmployeeDismissedEvent;
use App\Organization\Domain\Event\EmployeeHiredEvent;
use App\Organization\Domain\Event\EmployeeManagerChangedEvent;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\EmployeeNumber;
use App\Organization\Domain\ValueObject\EmployeeStatus;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\CreatedAt;

class Employee extends AggregateRoot
{
    private string $id;
    private string $organizationId;
    private string $userId;
    private string $positionId;
    private string $departmentId;
    private string $employeeNumber;
    private \DateTimeImmutable $hiredAt;
    private string $status;
    private ?string $managerId;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    private function __construct()
    {
    }

    public static function hire(
        EmployeeId $id,
        OrganizationId $organizationId,
        string $userId,
        PositionId $positionId,
        DepartmentId $departmentId,
        EmployeeNumber $employeeNumber,
        \DateTimeImmutable $hiredAt,
        ?EmployeeId $managerId = null,
    ): self {
        $employee = new self();
        $employee->id = $id->value();
        $employee->organizationId = $organizationId->value();
        $employee->userId = $userId;
        $employee->positionId = $positionId->value();
        $employee->departmentId = $departmentId->value();
        $employee->employeeNumber = $employeeNumber->value();
        $employee->hiredAt = $hiredAt;
        $employee->managerId = $managerId?->value();
        $employee->status = EmployeeStatus::Active->value;
        $employee->createdAt = new \DateTimeImmutable();
        $employee->updatedAt = null;

        $employee->recordEvent(new EmployeeHiredEvent(
            $id->value(),
            $organizationId->value(),
            $userId,
        ));

        return $employee;
    }

    public function dismiss(): void
    {
        $this->status = EmployeeStatus::Dismissed->value;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new EmployeeDismissedEvent(
            $this->id,
            $this->organizationId,
            $this->userId,
        ));
    }

    public function setManager(?EmployeeId $managerId): void
    {
        if (null !== $managerId && $managerId->value() === $this->id) {
            throw new \DomainException('Employee cannot be their own manager.');
        }

        $previousManagerId = $this->managerId;
        $this->managerId = $managerId?->value();
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordEvent(new EmployeeManagerChangedEvent(
            $this->id,
            $this->organizationId,
            $this->managerId,
            $previousManagerId,
        ));
    }

    public function changePosition(PositionId $newPositionId, DepartmentId $newDepartmentId): void
    {
        $this->positionId = $newPositionId->value();
        $this->departmentId = $newDepartmentId->value();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function goOnLeave(): void
    {
        $this->status = EmployeeStatus::OnLeave->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function returnFromLeave(): void
    {
        $this->status = EmployeeStatus::Active->value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): EmployeeId
    {
        return EmployeeId::fromString($this->id);
    }

    public function organizationId(): OrganizationId
    {
        return OrganizationId::fromString($this->organizationId);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function positionId(): PositionId
    {
        return PositionId::fromString($this->positionId);
    }

    public function departmentId(): DepartmentId
    {
        return DepartmentId::fromString($this->departmentId);
    }

    public function managerId(): ?EmployeeId
    {
        return null !== $this->managerId ? EmployeeId::fromString($this->managerId) : null;
    }

    public function employeeNumber(): EmployeeNumber
    {
        return new EmployeeNumber($this->employeeNumber);
    }

    public function hiredAt(): \DateTimeImmutable
    {
        return $this->hiredAt;
    }

    public function status(): EmployeeStatus
    {
        return EmployeeStatus::from($this->status);
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
        return EmployeeStatus::Active->value === $this->status;
    }

    public function isDismissed(): bool
    {
        return EmployeeStatus::Dismissed->value === $this->status;
    }

    public function isOnLeave(): bool
    {
        return EmployeeStatus::OnLeave->value === $this->status;
    }
}
