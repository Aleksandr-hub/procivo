<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class DepartmentCodeAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function withCode(string $code, string $organizationId): self
    {
        $e = new self(\sprintf('Department with code "%s" already exists in organization "%s".', $code, $organizationId));
        $e->statusCode = 409;
        $e->translationKey = 'error.department_code_exists';
        $e->translationParams = ['%code%' => $code, '%organizationId%' => $organizationId];

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
