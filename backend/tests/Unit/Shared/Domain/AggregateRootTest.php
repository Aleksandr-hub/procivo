<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain;

use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\DomainEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AggregateRootTest extends TestCase
{
    #[Test]
    public function itRecordsAndPullsDomainEvents(): void
    {
        $aggregate = new class extends AggregateRoot {
            public function doSomething(): void
            {
                $this->recordEvent(new TestEvent('first'));
                $this->recordEvent(new TestEvent('second'));
            }
        };

        $aggregate->doSomething();

        $events = $aggregate->pullDomainEvents();

        self::assertCount(2, $events);
        self::assertSame('first', $events[0]->payload);
        self::assertSame('second', $events[1]->payload);
    }

    #[Test]
    public function itClearsEventsAfterPull(): void
    {
        $aggregate = new class extends AggregateRoot {
            public function doSomething(): void
            {
                $this->recordEvent(new TestEvent('event'));
            }
        };

        $aggregate->doSomething();
        $aggregate->pullDomainEvents();

        self::assertCount(0, $aggregate->pullDomainEvents());
    }

    #[Test]
    public function itStartsWithNoEvents(): void
    {
        $aggregate = new class extends AggregateRoot {};

        self::assertCount(0, $aggregate->pullDomainEvents());
    }
}

/**
 * @internal
 */
final readonly class TestEvent implements DomainEvent
{
    public function __construct(
        public string $payload,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'test.event';
    }
}
