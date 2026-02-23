<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObject;

use App\Identity\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserStatusTest extends TestCase
{
    #[Test]
    public function itHasExpectedValues(): void
    {
        self::assertSame('pending', UserStatus::Pending->value);
        self::assertSame('active', UserStatus::Active->value);
        self::assertSame('blocked', UserStatus::Blocked->value);
    }

    #[Test]
    public function itCreatesFromString(): void
    {
        self::assertSame(UserStatus::Active, UserStatus::from('active'));
        self::assertSame(UserStatus::Pending, UserStatus::from('pending'));
        self::assertSame(UserStatus::Blocked, UserStatus::from('blocked'));
    }

    #[Test]
    public function itThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);

        UserStatus::from('invalid');
    }
}
