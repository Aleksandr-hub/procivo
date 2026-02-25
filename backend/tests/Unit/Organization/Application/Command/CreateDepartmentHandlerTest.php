<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CreateDepartment\CreateDepartmentCommand;
use App\Organization\Application\Command\CreateDepartment\CreateDepartmentHandler;
use App\Organization\Domain\Entity\Department;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Exception\DepartmentCodeAlreadyExistsException;
use App\Organization\Domain\Repository\DepartmentRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPath;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationName;
use App\Organization\Domain\ValueObject\OrganizationSlug;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CreateDepartmentHandlerTest extends TestCase
{
    #[Test]
    public function itCreatesARootDepartment(): void
    {
        $orgId = OrganizationId::generate();
        $organization = Organization::create($orgId, new OrganizationName('Test'), new OrganizationSlug('test'), null, 'user-1');

        $orgRepo = $this->createMock(OrganizationRepositoryInterface::class);
        $deptRepo = $this->createMock(DepartmentRepositoryInterface::class);

        $orgRepo->method('findById')->willReturn($organization);
        $deptRepo->method('existsByCode')->willReturn(false);
        $deptRepo->expects(self::once())->method('save');

        $handler = new CreateDepartmentHandler($orgRepo, $deptRepo);

        $handler(new CreateDepartmentCommand(
            id: DepartmentId::generate()->value(),
            organizationId: $orgId->value(),
            parentId: null,
            name: 'Engineering',
            code: 'ENG',
            description: null,
            sortOrder: 0,
        ));
    }

    #[Test]
    public function itCreatesAChildDepartment(): void
    {
        $orgId = OrganizationId::generate();
        $parentId = DepartmentId::generate();
        $organization = Organization::create($orgId, new OrganizationName('Test'), new OrganizationSlug('test'), null, 'user-1');
        $parent = Department::create(
            $parentId, $orgId, null, 'Parent', new DepartmentCode('PAR'),
            null, 0, 0, DepartmentPath::root()->append($parentId),
        );

        $orgRepo = $this->createMock(OrganizationRepositoryInterface::class);
        $deptRepo = $this->createMock(DepartmentRepositoryInterface::class);

        $orgRepo->method('findById')->willReturn($organization);
        $deptRepo->method('existsByCode')->willReturn(false);
        $deptRepo->method('findById')->willReturn($parent);
        $deptRepo->expects(self::once())->method('save');

        $handler = new CreateDepartmentHandler($orgRepo, $deptRepo);

        $handler(new CreateDepartmentCommand(
            id: DepartmentId::generate()->value(),
            organizationId: $orgId->value(),
            parentId: $parentId->value(),
            name: 'Sub Department',
            code: 'SUB',
            description: null,
            sortOrder: 0,
        ));
    }

    #[Test]
    public function itThrowsWhenCodeAlreadyExists(): void
    {
        $orgId = OrganizationId::generate();
        $organization = Organization::create($orgId, new OrganizationName('Test'), new OrganizationSlug('test'), null, 'user-1');

        $orgRepo = $this->createMock(OrganizationRepositoryInterface::class);
        $deptRepo = $this->createMock(DepartmentRepositoryInterface::class);

        $orgRepo->method('findById')->willReturn($organization);
        $deptRepo->method('existsByCode')->willReturn(true);

        $handler = new CreateDepartmentHandler($orgRepo, $deptRepo);

        $this->expectException(DepartmentCodeAlreadyExistsException::class);

        $handler(new CreateDepartmentCommand(
            id: DepartmentId::generate()->value(),
            organizationId: $orgId->value(),
            parentId: null,
            name: 'Engineering',
            code: 'ENG',
            description: null,
            sortOrder: 0,
        ));
    }
}
