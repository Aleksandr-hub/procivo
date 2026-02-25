<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class UserAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';

    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withEmail(string $email): self
    {
        $e = new self(\sprintf('User with email "%s" already exists.', $email));
        $e->statusCode = 409;
        $e->translationKey = 'error.user_already_exists';
        $e->translationParams = ['%email%' => $email];

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
