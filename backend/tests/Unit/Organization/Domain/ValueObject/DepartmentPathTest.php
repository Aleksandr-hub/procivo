<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObject;

use App\Organization\Domain\ValueObject\DepartmentId;
use App\Organization\Domain\ValueObject\DepartmentPath;
use App\Shared\Domain\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DepartmentPathTest extends TestCase
{
    #[Test]
    public function itCreatesRootPath(): void
    {
        $path = DepartmentPath::root();

        self::assertSame('/', $path->value());
        self::assertSame(0, $path->depth());
    }

    #[Test]
    public function itAppendsId(): void
    {
        $id = DepartmentId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b');
        $path = DepartmentPath::root()->append($id);

        self::assertSame('/01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b/', $path->value());
        self::assertSame(1, $path->depth());
    }

    #[Test]
    public function itCalculatesDepthCorrectly(): void
    {
        $id1 = DepartmentId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b');
        $id2 = DepartmentId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6c');
        $path = DepartmentPath::root()->append($id1)->append($id2);

        self::assertSame(2, $path->depth());
    }

    #[Test]
    public function itChecksContainsId(): void
    {
        $id1 = DepartmentId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b');
        $id2 = DepartmentId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6c');
        $id3 = DepartmentId::fromString('01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6d');
        $path = DepartmentPath::root()->append($id1)->append($id2);

        self::assertTrue($path->contains($id1));
        self::assertTrue($path->contains($id2));
        self::assertFalse($path->contains($id3));
    }

    #[Test]
    public function itRejectsInvalidPathFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DepartmentPath('invalid-path');
    }

    #[Test]
    public function itAcceptsValidPath(): void
    {
        $path = new DepartmentPath('/01944b8a-5c6e-7d8f-9a0b-1c2d3e4f5a6b/');

        self::assertSame(1, $path->depth());
    }
}
