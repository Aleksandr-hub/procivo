<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

readonly class HashedPassword implements \Stringable
{
    private string $value;

    public function __construct(string $hashedPassword)
    {
        if ('' === $hashedPassword) {
            throw new InvalidArgumentException('Password hash cannot be empty.');
        }

        $this->value = $hashedPassword;
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
