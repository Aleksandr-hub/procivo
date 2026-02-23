<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    #[Test]
    public function itGeneratesAValidUuid(): void
    {
        $uuid = Uuid::generate();

        self::assertNotEmpty($uuid->value());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $uuid->value(),
        );
    }

    #[Test]
    public function itCreatesFromString(): void
    {
        $value = '01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b';
        $uuid = Uuid::fromString($value);

        self::assertSame($value, $uuid->value());
        self::assertSame($value, (string) $uuid);
    }

    #[Test]
    public function itThrowsOnInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Uuid('not-a-uuid');
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $uuid1 = Uuid::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b');
        $uuid2 = Uuid::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b');
        $uuid3 = Uuid::generate();

        self::assertTrue($uuid1->equals($uuid2));
        self::assertFalse($uuid1->equals($uuid3));
    }
}
