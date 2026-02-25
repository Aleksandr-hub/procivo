<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class InvitationNotFoundException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withId(string $id): self
    {
        $e = new self(\sprintf('Invitation with ID "%s" not found.', $id));
        $e->statusCode = 404;
        $e->translationKey = 'error.invitation_not_found';
        $e->translationParams = ['%id%' => $id];

        return $e;
    }

    public static function withToken(string $token): self
    {
        $e = new self('Invitation not found for the given token.');
        $e->statusCode = 404;
        $e->translationKey = 'error.invitation_not_found_by_token';
        $e->translationParams = [];

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
