<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class InvalidCredentialsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';

    /** @var array<string, string> */
    private array $translationParams = [];

    public static function invalidEmailOrPassword(): self
    {
        $e = new self('Invalid email or password.');
        $e->statusCode = 401;
        $e->translationKey = 'error.invalid_email_or_password';
        $e->translationParams = [];

        return $e;
    }

    public static function invalidRefreshToken(): self
    {
        $e = new self('Invalid or expired refresh token.');
        $e->statusCode = 401;
        $e->translationKey = 'error.invalid_refresh_token';
        $e->translationParams = [];

        return $e;
    }

    public static function wrongCurrentPassword(): self
    {
        $e = new self('Current password is incorrect.');
        $e->statusCode = 400;
        $e->translationKey = 'error.wrong_current_password';
        $e->translationParams = [];

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
