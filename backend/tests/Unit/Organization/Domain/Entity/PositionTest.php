<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entity;

use App\Organization\Domain\Entity\Position;
use App\Organization\Domain\Event\PositionCreatedEvent;
use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\PositionId;
use App\Organization\Domain\ValueObject\PositionName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PositionTest extends TestCase
{
    #[Test]
    public function itCreatesAPosition(): void
    {
        $position = $this->createPosition();

        self::assertNotEmpty($position->id()->value());
        self::assertSame('Senior Developer', $position->name()->value());
        self::assertSame('Lead the team', $position->description());
        self::assertSame(1, $position->sortOrder());
        self::assertTrue($position->isHead());
    }

    #[Test]
    public function itRecordsPositionCreatedEvent(): void
    {
        $position = $this->createPosition();
        $events = $position->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(PositionCreatedEvent::class, $events[0]);
        self::assertSame('Senior Developer', $events[0]->name);
    }

    #[Test]
    public function itUpdatesPosition(): void
    {
        $position = $this->createPosition();

        $position->update(new PositionName('Junior Developer'), 'New desc', 2, false);

        self::assertSame('Junior Developer', $position->name()->value());
        self::assertSame('New desc', $position->description());
        self::assertSame(2, $position->sortOrder());
        self::assertFalse($position->isHead());
        self::assertNotNull($position->updatedAt());
    }

    private function createPosition(): Position
    {
        return Position::create(
            id: PositionId::generate(),
            organizationId: OrganizationId::generate(),
            departmentId: DepartmentId::generate(),
            name: new PositionName('Senior Developer'),
            description: 'Lead the team',
            sortOrder: 1,
            isHead: true,
        );
    }
}
