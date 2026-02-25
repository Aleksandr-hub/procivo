<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

readonly class OrganizationName implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ('' === $value || mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Organization name must be between 1 and 255 characters.');
        }

        $this->value = $value;
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
