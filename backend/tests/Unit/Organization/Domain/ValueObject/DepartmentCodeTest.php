<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObject;

use App\Organization\Domain\ValueObject\DepartmentCode;
use App\Shared\Domain\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DepartmentCodeTest extends TestCase
{
    #[Test]
    public function itCreatesAValidCode(): void
    {
        $code = new DepartmentCode('ENG-01');

        self::assertSame('ENG-01', $code->value());
        self::assertSame('ENG-01', (string) $code);
    }

    #[Test]
    public function itRejectsEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DepartmentCode('');
    }

    #[Test]
    public function itRejectsCodeWithSpecialChars(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DepartmentCode('ENG/01');
    }

    #[Test]
    public function itRejectsCodeTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DepartmentCode(str_repeat('A', 51));
    }
}
