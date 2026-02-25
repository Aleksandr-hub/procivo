<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class EmployeeAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function forUser(string $userId, string $organizationId): self
    {
        $e = new self(\sprintf('User "%s" is already an employee of organization "%s".', $userId, $organizationId));
        $e->statusCode = 409;
        $e->translationKey = 'error.employee_already_exists';
        $e->translationParams = ['%userId%' => $userId, '%organizationId%' => $organizationId];

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
