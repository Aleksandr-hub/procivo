<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObject;

use App\Identity\Domain\ValueObject\HashedPassword;
use App\Shared\Domain\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HashedPasswordTest extends TestCase
{
    #[Test]
    public function itCreatesFromHash(): void
    {
        $password = new HashedPassword('$2y$13$somehash');

        self::assertSame('$2y$13$somehash', $password->value());
        self::assertSame('$2y$13$somehash', (string) $password);
    }

    #[Test]
    public function itThrowsOnEmptyHash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HashedPassword('');
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $pw1 = new HashedPassword('hash1');
        $pw2 = new HashedPassword('hash1');
        $pw3 = new HashedPassword('hash2');

        self::assertTrue($pw1->equals($pw2));
        self::assertFalse($pw1->equals($pw3));
    }
}
