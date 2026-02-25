<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class InvitationAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function forEmail(string $email, string $organizationId): self
    {
        $e = new self(\sprintf(
            'A pending invitation for "%s" already exists in organization "%s".',
            $email,
            $organizationId,
        ));
        $e->statusCode = 409;
        $e->translationKey = 'error.invitation_already_exists';
        $e->translationParams = ['%email%' => $email, '%organizationId%' => $organizationId];

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
