<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class PermissionNotFoundException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withId(string $id): self
    {
        $e = new self(\sprintf('Permission with ID "%s" not found.', $id));
        $e->statusCode = 404;
        $e->translationKey = 'error.permission_not_found';
        $e->translationParams = ['%id%' => $id];

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
