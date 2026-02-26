<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class InvalidProcessGraphException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    /**
     * @param list<string> $errors
     */
    public static function withErrors(array $errors): self
    {
        $e = new self(\sprintf('Invalid process graph: %s', implode('; ', $errors)));
        $e->statusCode = 422;
        $e->translationKey = 'error.invalid_process_graph';
        $e->translationParams = ['%errors%' => implode('; ', $errors)];

        return $e;
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /** @return array<string, string> */
    public function getTranslationParams(): array
    {
        return $this->translationParams;
    }
}
