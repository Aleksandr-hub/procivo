<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entity;

use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Event\OrganizationCreatedEvent;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationName;
use App\Organization\Domain\ValueObject\OrganizationSlug;
use App\Organization\Domain\ValueObject\OrganizationStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    #[Test]
    public function itCreatesAnOrganization(): void
    {
        $org = $this->createOrganization();

        self::assertNotEmpty($org->id()->value());
        self::assertSame('Acme Corp', $org->name()->value());
        self::assertSame('acme-corp', $org->slug()->value());
        self::assertSame('Test organization', $org->description());
        self::assertSame(OrganizationStatus::Active, $org->status());
        self::assertSame('owner-user-id', $org->ownerUserId());
        self::assertTrue($org->isActive());
        self::assertFalse($org->isSuspended());
    }

    #[Test]
    public function itRecordsOrganizationCreatedEvent(): void
    {
        $org = $this->createOrganization();
        $events = $org->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrganizationCreatedEvent::class, $events[0]);
        self::assertSame('Acme Corp', $events[0]->name);
        self::assertSame('owner-user-id', $events[0]->ownerUserId);
    }

    #[Test]
    public function itUpdatesOrganization(): void
    {
        $org = $this->createOrganization();

        $org->update(new OrganizationName('New Name'), 'New description');

        self::assertSame('New Name', $org->name()->value());
        self::assertSame('New description', $org->description());
        self::assertNotNull($org->updatedAt());
    }

    #[Test]
    public function itSuspendsOrganization(): void
    {
        $org = $this->createOrganization();

        $org->suspend();

        self::assertSame(OrganizationStatus::Suspended, $org->status());
        self::assertTrue($org->isSuspended());
        self::assertFalse($org->isActive());
    }

    #[Test]
    public function itActivatesSuspendedOrganization(): void
    {
        $org = $this->createOrganization();
        $org->suspend();

        $org->activate();

        self::assertSame(OrganizationStatus::Active, $org->status());
        self::assertTrue($org->isActive());
    }

    #[Test]
    public function itChecksOwnership(): void
    {
        $org = $this->createOrganization();

        self::assertTrue($org->isOwner('owner-user-id'));
        self::assertFalse($org->isOwner('other-user-id'));
    }

    private function createOrganization(): Organization
    {
        return Organization::create(
            id: OrganizationId::generate(),
            name: new OrganizationName('Acme Corp'),
            slug: new OrganizationSlug('acme-corp'),
            description: 'Test organization',
            ownerUserId: 'owner-user-id',
        );
    }
}
