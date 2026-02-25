<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class CannotDeleteSystemRoleException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withId(string $id): self
    {
        $e = new self(\sprintf('Cannot delete system role with ID "%s".', $id));
        $e->statusCode = 409;
        $e->translationKey = 'error.cannot_delete_system_role';
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
