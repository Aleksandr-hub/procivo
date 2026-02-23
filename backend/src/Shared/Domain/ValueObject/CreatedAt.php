<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

readonly class CreatedAt implements \Stringable
{
    private \DateTimeImmutable $value;

    public function __construct(?\DateTimeImmutable $value = null)
    {
        $this->value = $value ?? new \DateTimeImmutable();
    }

    public static function now(): self
    {
        return new self();
    }

    public static function fromString(string $datetime): self
    {
        return new self(new \DateTimeImmutable($datetime));
    }

    public function value(): \DateTimeImmutable
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value == $other->value;
    }

    public function __toString(): string
    {
        return $this->value->format(\DateTimeInterface::ATOM);
    }
}
