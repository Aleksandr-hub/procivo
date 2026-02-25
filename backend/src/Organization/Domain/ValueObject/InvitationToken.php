<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

readonly class InvitationToken implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ('' === $value || mb_strlen($value) > 128) {
            throw new InvalidArgumentException('Invitation token must be between 1 and 128 characters.');
        }

        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
