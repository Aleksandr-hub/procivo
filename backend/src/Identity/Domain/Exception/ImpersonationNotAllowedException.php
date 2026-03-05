<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class ImpersonationNotAllowedException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';

    /** @var array<string, string> */
    private array $translationParams = [];

    public static function userNotFound(): self
    {
        $e = new self('Target user not found or inactive.');
        $e->statusCode = 404;
        $e->translationKey = 'error.impersonation_user_not_found';

        return $e;
    }

    public static function cannotImpersonateSuperAdmin(): self
    {
        $e = new self('Cannot impersonate a super admin user.');
        $e->statusCode = 403;
        $e->translationKey = 'error.impersonation_cannot_impersonate_super_admin';

        return $e;
    }

    public static function alreadyImpersonating(): self
    {
        $e = new self('Cannot start impersonation while already impersonating.');
        $e->statusCode = 403;
        $e->translationKey = 'error.impersonation_already_impersonating';

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
