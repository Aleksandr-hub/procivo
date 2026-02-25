<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

class InvalidArgumentException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = 'error.invalid_argument';

    /** @var array<string, string> */
    private array $translationParams = [];

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /**
     * @return array<string, string>
     */
    public function getTranslationParams(): array
    {
        return $this->translationParams;
    }
}
