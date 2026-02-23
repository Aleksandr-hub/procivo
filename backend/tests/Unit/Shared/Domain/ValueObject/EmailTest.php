<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    #[Test]
    public function itCreatesAValidEmail(): void
    {
        $email = new Email('user@example.com');

        self::assertSame('user@example.com', $email->value());
        self::assertSame('user@example.com', (string) $email);
    }

    #[Test]
    public function itNormalizesToLowercase(): void
    {
        $email = new Email('User@Example.COM');

        self::assertSame('user@example.com', $email->value());
    }

    #[Test]
    public function itTrimsWhitespace(): void
    {
        $email = new Email('  user@example.com  ');

        self::assertSame('user@example.com', $email->value());
    }

    #[Test]
    #[DataProvider('invalidEmails')]
    public function itThrowsOnInvalidEmail(string $invalid): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email($invalid);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEmails(): iterable
    {
        yield 'empty string' => [''];
        yield 'no @' => ['userexample.com'];
        yield 'no domain' => ['user@'];
        yield 'no user' => ['@example.com'];
        yield 'spaces in middle' => ['user @example.com'];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        $email1 = new Email('user@example.com');
        $email2 = new Email('USER@example.com');
        $email3 = new Email('other@example.com');

        self::assertTrue($email1->equals($email2));
        self::assertFalse($email1->equals($email3));
    }
}
