<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

readonly class DepartmentCode implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ('' === $value || mb_strlen($value) > 50) {
            throw new InvalidArgumentException('Department code must be between 1 and 50 characters.');
        }

        if (!preg_match('/^[a-zA-Z0-9-]+$/', $value)) {
            throw new InvalidArgumentException(\sprintf('Invalid department code: "%s". Only alphanumeric characters and hyphens allowed.', $value));
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
