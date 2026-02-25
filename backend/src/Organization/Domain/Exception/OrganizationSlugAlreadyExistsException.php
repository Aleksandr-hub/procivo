<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class OrganizationSlugAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withSlug(string $slug): self
    {
        $e = new self(\sprintf('Organization with slug "%s" already exists.', $slug));
        $e->statusCode = 409;
        $e->translationKey = 'error.organization_slug_exists';
        $e->translationParams = ['%slug%' => $slug];

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
