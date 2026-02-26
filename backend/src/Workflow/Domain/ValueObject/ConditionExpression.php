<?php

declare(strict_types=1);

namespace App\Workflow\Domain\ValueObject;

final readonly class ConditionExpression implements \Stringable
{
    private string $expression;

    public function __construct(string $expression)
    {
        $trimmed = trim($expression);
        if ('' === $trimmed) {
            throw new \InvalidArgumentException('Condition expression cannot be empty.');
        }

        $this->expression = $trimmed;
    }

    public static function fromString(string $expression): self
    {
        return new self($expression);
    }

    public function value(): string
    {
        return $this->expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}
