<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\CreatedAt;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CreatedAtTest extends TestCase
{
    #[Test]
    public function itCreatesWithCurrentTime(): void
    {
        $before = new \DateTimeImmutable();
        $createdAt = CreatedAt::now();
        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $createdAt->value());
        self::assertLessThanOrEqual($after, $createdAt->value());
    }

    #[Test]
    public function itCreatesFromString(): void
    {
        $createdAt = CreatedAt::fromString('2026-01-15 10:30:00');

        self::assertSame('2026-01-15', $createdAt->value()->format('Y-m-d'));
    }

    #[Test]
    public function itConvertsToString(): void
    {
        $createdAt = CreatedAt::fromString('2026-01-15T10:30:00+00:00');

        self::assertSame('2026-01-15T10:30:00+00:00', (string) $createdAt);
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $dt = new \DateTimeImmutable('2026-01-15 10:30:00');
        $createdAt1 = new CreatedAt($dt);
        $createdAt2 = new CreatedAt($dt);
        $createdAt3 = CreatedAt::now();

        self::assertTrue($createdAt1->equals($createdAt2));
        self::assertFalse($createdAt1->equals($createdAt3));
    }
}
