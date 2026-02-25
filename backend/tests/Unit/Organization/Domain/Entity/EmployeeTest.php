<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entity;

use App\Organization\Domain\Entity\Employee;
use App\Organization\Domain\Event\EmployeeDismissedEvent;
use App\Organization\Domain\Event\EmployeeHiredEvent;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\EmployeeNumber;
use App\Organization\Domain\ValueObject\EmployeeStatus;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    #[Test]
    public function itHiresAnEmployee(): void
    {
        $employee = $this->createEmployee();

        self::assertNotEmpty($employee->id()->value());
        self::assertSame('user-123', $employee->userId());
        self::assertSame('EMP-001', $employee->employeeNumber()->value());
        self::assertSame(EmployeeStatus::Active, $employee->status());
        self::assertTrue($employee->isActive());
        self::assertFalse($employee->isDismissed());
    }

    #[Test]
    public function itRecordsEmployeeHiredEvent(): void
    {
        $employee = $this->createEmployee();
        $events = $employee->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(EmployeeHiredEvent::class, $events[0]);
        self::assertSame('user-123', $events[0]->userId);
    }

    #[Test]
    public function itDismissesEmployee(): void
    {
        $employee = $this->createEmployee();
        $employee->pullDomainEvents();

        $employee->dismiss();

        self::assertSame(EmployeeStatus::Dismissed, $employee->status());
        self::assertTrue($employee->isDismissed());
        self::assertFalse($employee->isActive());
    }

    #[Test]
    public function itRecordsEmployeeDismissedEvent(): void
    {
        $employee = $this->createEmployee();
        $employee->pullDomainEvents();

        $employee->dismiss();

        $events = $employee->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(EmployeeDismissedEvent::class, $events[0]);
    }

    #[Test]
    public function itChangesPosition(): void
    {
        $employee = $this->createEmployee();
        $newPositionId = PositionId::generate();
        $newDeptId = DepartmentId::generate();

        $employee->changePosition($newPositionId, $newDeptId);

        self::assertSame($newPositionId->value(), $employee->positionId()->value());
        self::assertSame($newDeptId->value(), $employee->departmentId()->value());
        self::assertNotNull($employee->updatedAt());
    }

    #[Test]
    public function itHandlesLeave(): void
    {
        $employee = $this->createEmployee();

        $employee->goOnLeave();

        self::assertSame(EmployeeStatus::OnLeave, $employee->status());
        self::assertTrue($employee->isOnLeave());

        $employee->returnFromLeave();

        self::assertSame(EmployeeStatus::Active, $employee->status());
        self::assertTrue($employee->isActive());
    }

    private function createEmployee(): Employee
    {
        return Employee::hire(
            id: EmployeeId::generate(),
            organizationId: OrganizationId::generate(),
            userId: 'user-123',
            positionId: PositionId::generate(),
            departmentId: DepartmentId::generate(),
            employeeNumber: new EmployeeNumber('EMP-001'),
            hiredAt: new \DateTimeImmutable('2024-01-15'),
        );
    }
}
