<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class RoleAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withName(string $name, string $organizationId): self
    {
        $e = new self(\sprintf('Role with name "%s" already exists in organization "%s".', $name, $organizationId));
        $e->statusCode = 409;
        $e->translationKey = 'error.role_already_exists';
        $e->translationParams = ['%name%' => $name, '%organizationId%' => $organizationId];

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
