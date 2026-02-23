<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObject;

use App\Identity\Domain\ValueObject\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    #[Test]
    public function itGeneratesAValidUserId(): void
    {
        $id = UserId::generate();

        self::assertNotEmpty($id->value());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id->value(),
        );
    }

    #[Test]
    public function itCreatesFromString(): void
    {
        $value = '01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b';
        $id = UserId::fromString($value);

        self::assertSame($value, $id->value());
        self::assertInstanceOf(UserId::class, $id);
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $id1 = UserId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b');
        $id2 = UserId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b');
        $id3 = UserId::generate();

        self::assertTrue($id1->equals($id2));
        self::assertFalse($id1->equals($id3));
    }
}
