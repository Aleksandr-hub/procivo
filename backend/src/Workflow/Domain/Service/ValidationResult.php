<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

final readonly class ValidationResult
{
    /**
     * @param list<string> $errors
     */
    private function __construct(
        private bool $valid,
        private array $errors,
    ) {
    }

    public static function success(): self
    {
        return new self(true, []);
    }

    /**
     * @param list<string> $errors
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
