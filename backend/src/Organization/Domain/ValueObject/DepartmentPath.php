<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

readonly class DepartmentPath implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        if ('/' !== $value && !preg_match('#^(/[a-f0-9-]{36})+/$#', $value)) {
            throw new InvalidArgumentException(\sprintf('Invalid department path: "%s". Must be format "/uuid/uuid/.../" or "/".', $value));
        }

        $this->value = $value;
    }

    public static function root(): self
    {
        return new self('/');
    }

    public function append(DepartmentId $id): self
    {
        return new self($this->value.$id->value().'/');
    }

    public function depth(): int
    {
        if ('/' === $this->value) {
            return 0;
        }

        return substr_count($this->value, '/') - 1;
    }

    public function contains(DepartmentId $id): bool
    {
        return str_contains($this->value, '/'.$id->value().'/');
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
