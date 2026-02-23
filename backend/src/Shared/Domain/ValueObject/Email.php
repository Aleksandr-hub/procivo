<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

readonly class Email implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if (!filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\sprintf('Invalid email: "%s".', $value));
        }

        $this->value = mb_strtolower($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
