<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class InvalidTaskTransitionException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function forTransition(string $transition, string $currentStatus): self
    {
        $e = new self(\sprintf('Transition "%s" is not allowed from status "%s".', $transition, $currentStatus));
        $e->statusCode = 422;
        $e->translationKey = 'error.invalid_task_transition';
        $e->translationParams = ['%transition%' => $transition, '%status%' => $currentStatus];

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
