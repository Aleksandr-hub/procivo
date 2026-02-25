<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

readonly class OrganizationSlug implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $value)) {
            throw new InvalidArgumentException(\sprintf('Invalid organization slug: "%s". Must be lowercase alphanumeric with hyphens, 1-63 chars.', $value));
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
