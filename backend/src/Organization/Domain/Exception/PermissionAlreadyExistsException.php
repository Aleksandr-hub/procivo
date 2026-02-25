<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class PermissionAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function forRoleResourceAndAction(string $roleId, string $resource, string $action): self
    {
        $e = new self(\sprintf('Permission for resource "%s" and action "%s" already exists for role "%s".', $resource, $action, $roleId));
        $e->statusCode = 409;
        $e->translationKey = 'error.permission_already_exists';
        $e->translationParams = ['%roleId%' => $roleId, '%resource%' => $resource, '%action%' => $action];

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
