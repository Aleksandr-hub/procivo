<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\HireEmployee\HireEmployeeCommand;
use App\Organization\Application\Command\HireEmployee\HireEmployeeHandler;
use App\Organization\Domain\Entity\Department;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Entity\Position;
use App\Organization\Domain\Exception\EmployeeAlreadyExistsException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\EmployeeRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Repository\PositionRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPath;
use App\Organization\Domain\ValueObject\EmployeeId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationName;
use App\Organization\Domain\ValueObject\OrganizationSlug;
use App\Organization\Domain\ValueObject\PositionId;
use App\Organization\Domain\ValueObject\PositionName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HireEmployeeHandlerTest extends TestCase
{
    #[Test]
    public function itHiresAnEmployee(): void
    {
        $orgId = OrganizationId::generate();
        $deptId = DepartmentId::generate();
        $posId = PositionId::generate();

        $orgRepo = $this->createMock(OrganizationRepositoryInterface::class);
        $deptRepo = $this->createMock(DepartmentRepositoryInterface::class);
        $posRepo = $this->createMock(PositionRepositoryInterface::class);
        $empRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $orgRepo->method('findById')->willReturn(
            Organization::create($orgId, new OrganizationName('Test'), new OrganizationSlug('test'), null, 'owner'),
        );
        $deptRepo->method('findById')->willReturn(
            Department::create($deptId, $orgId, null, 'Dept', new DepartmentCode('D1'), null, 0, 0, DepartmentPath::root()->append($deptId)),
        );
        $posRepo->method('findById')->willReturn(
            Position::create($posId, $orgId, $deptId, new PositionName('Dev'), null, 0, false),
        );
        $empRepo->method('existsByUserIdAndOrganizationId')->willReturn(false);
        $empRepo->expects(self::once())->method('save');

        $handler = new HireEmployeeHandler($orgRepo, $deptRepo, $posRepo, $empRepo);

        $handler(new HireEmployeeCommand(
            id: EmployeeId::generate()->value(),
            organizationId: $orgId->value(),
            userId: 'user-123',
            positionId: $posId->value(),
            departmentId: $deptId->value(),
            employeeNumber: 'EMP-001',
            hiredAt: '2024-01-15',
        ));
    }

    #[Test]
    public function itThrowsWhenAlreadyEmployed(): void
    {
        $orgId = OrganizationId::generate();
        $deptId = DepartmentId::generate();
        $posId = PositionId::generate();

        $orgRepo = $this->createMock(OrganizationRepositoryInterface::class);
        $deptRepo = $this->createMock(DepartmentRepositoryInterface::class);
        $posRepo = $this->createMock(PositionRepositoryInterface::class);
        $empRepo = $this->createMock(EmployeeRepositoryInterface::class);

        $orgRepo->method('findById')->willReturn(
            Organization::create($orgId, new OrganizationName('Test'), new OrganizationSlug('test'), null, 'owner'),
        );
        $deptRepo->method('findById')->willReturn(
            Department::create($deptId, $orgId, null, 'Dept', new DepartmentCode('D1'), null, 0, 0, DepartmentPath::root()->append($deptId)),
        );
        $posRepo->method('findById')->willReturn(
            Position::create($posId, $orgId, $deptId, new PositionName('Dev'), null, 0, false),
        );
        $empRepo->method('existsByUserIdAndOrganizationId')->willReturn(true);

        $handler = new HireEmployeeHandler($orgRepo, $deptRepo, $posRepo, $empRepo);

        $this->expectException(EmployeeAlreadyExistsException::class);

        $handler(new HireEmployeeCommand(
            id: EmployeeId::generate()->value(),
            organizationId: $orgId->value(),
            userId: 'user-123',
            positionId: $posId->value(),
            departmentId: $deptId->value(),
            employeeNumber: 'EMP-001',
            hiredAt: '2024-01-15',
        ));
    }
}
