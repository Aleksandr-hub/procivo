<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entity;

use App\Organization\Domain\Entity\Department;
use App\Organization\Domain\Event\DepartmentCreatedEvent;
use App\Organization\Domain\Event\DepartmentMovedEvent;
use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPath;
use App\Organization\Domain\ValueObject\DepartmentStatus;
use App\Organization\Domain\ValueObject\OrganizationId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DepartmentTest extends TestCase
{
    #[Test]
    public function itCreatesARootDepartment(): void
    {
        $deptId = DepartmentId::generate();
        $dept = Department::create(
            id: $deptId,
            organizationId: OrganizationId::generate(),
            parentId: null,
            name: 'Engineering',
            code: new DepartmentCode('ENG'),
            description: 'Engineering department',
            sortOrder: 0,
            level: 0,
            path: DepartmentPath::root()->append($deptId),
        );

        self::assertSame('Engineering', $dept->name());
        self::assertSame('ENG', $dept->code()->value());
        self::assertNull($dept->parentId());
        self::assertSame(0, $dept->level());
        self::assertTrue($dept->isActive());
    }

    #[Test]
    public function itRecordsDepartmentCreatedEvent(): void
    {
        $dept = $this->createDepartment();
        $events = $dept->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(DepartmentCreatedEvent::class, $events[0]);
        self::assertSame('Engineering', $events[0]->name);
    }

    #[Test]
    public function itMovesToNewParent(): void
    {
        $dept = $this->createDepartment();
        $dept->pullDomainEvents();

        $newParentId = DepartmentId::generate();
        $newPath = DepartmentPath::root()->append($newParentId)->append($dept->id());

        $dept->moveTo($newParentId, 1, $newPath);

        self::assertSame($newParentId->value(), $dept->parentId()?->value());
        self::assertSame(1, $dept->level());
        self::assertTrue($dept->path()->contains($newParentId));
    }

    #[Test]
    public function itRecordsDepartmentMovedEvent(): void
    {
        $dept = $this->createDepartment();
        $dept->pullDomainEvents();

        $newParentId = DepartmentId::generate();
        $newPath = DepartmentPath::root()->append($newParentId)->append($dept->id());
        $dept->moveTo($newParentId, 1, $newPath);

        $events = $dept->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(DepartmentMovedEvent::class, $events[0]);
        self::assertNull($events[0]->oldParentId);
        self::assertSame($newParentId->value(), $events[0]->newParentId);
    }

    #[Test]
    public function itArchivesDepartment(): void
    {
        $dept = $this->createDepartment();

        $dept->archive();

        self::assertSame(DepartmentStatus::Archived, $dept->status());
        self::assertTrue($dept->isArchived());
        self::assertFalse($dept->isActive());
    }

    #[Test]
    public function itUpdatesDepartment(): void
    {
        $dept = $this->createDepartment();

        $dept->update('New Name', 'New desc', 5);

        self::assertSame('New Name', $dept->name());
        self::assertSame('New desc', $dept->description());
        self::assertSame(5, $dept->sortOrder());
        self::assertNotNull($dept->updatedAt());
    }

    private function createDepartment(): Department
    {
        $deptId = DepartmentId::generate();

        return Department::create(
            id: $deptId,
            organizationId: OrganizationId::generate(),
            parentId: null,
            name: 'Engineering',
            code: new DepartmentCode('ENG'),
            description: null,
            sortOrder: 0,
            level: 0,
            path: DepartmentPath::root()->append($deptId),
        );
    }
}
