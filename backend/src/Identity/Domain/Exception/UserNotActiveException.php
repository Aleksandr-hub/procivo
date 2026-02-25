<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class UserNotActiveException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';

    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withId(string $userId): self
    {
        $e = new self(\sprintf('User "%s" is not active.', $userId));
        $e->statusCode = 403;
        $e->translationKey = 'error.user_not_active';
        $e->translationParams = ['%userId%' => $userId];

        return $e;
    }

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
