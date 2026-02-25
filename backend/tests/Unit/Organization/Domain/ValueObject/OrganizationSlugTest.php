<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObject;

use App\Organization\Domain\ValueObject\OrganizationSlug;
use App\Shared\Domain\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrganizationSlugTest extends TestCase
{
    #[Test]
    public function itCreatesAValidSlug(): void
    {
        $slug = new OrganizationSlug('acme-corp');

        self::assertSame('acme-corp', $slug->value());
        self::assertSame('acme-corp', (string) $slug);
    }

    #[Test]
    public function itAcceptsSingleCharacterSlug(): void
    {
        $slug = new OrganizationSlug('a');

        self::assertSame('a', $slug->value());
    }

    #[Test]
    public function itRejectsEmptySlug(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrganizationSlug('');
    }

    #[Test]
    public function itRejectsSlugWithUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrganizationSlug('Acme-Corp');
    }

    #[Test]
    public function itRejectsSlugStartingWithHyphen(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrganizationSlug('-acme');
    }

    #[Test]
    public function itRejectsSlugWithSpecialChars(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrganizationSlug('acme_corp');
    }
}
