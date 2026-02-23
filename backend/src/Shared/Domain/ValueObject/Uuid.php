<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid as SymfonyUuid;

readonly class Uuid implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        if (!SymfonyUuid::isValid($value)) {
            throw new \InvalidArgumentException(\sprintf('Invalid UUID: "%s".', $value));
        }

        $this->value = $value;
    }

    public static function generate(): static
    {
        return new static(SymfonyUuid::v7()->toRfc4122()); // @phpstan-ignore new.static
    }

    public static function fromString(string $value): static
    {
        return new static($value); // @phpstan-ignore new.static
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
