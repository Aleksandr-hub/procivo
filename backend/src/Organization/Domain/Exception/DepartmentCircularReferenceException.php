<?php

declare(strict_types=1);

namespace App\Organization\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class DepartmentCircularReferenceException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];

    public static function forDepartment(string $departmentId): self
    {
        $e = new self(\sprintf('Moving department "%s" would create a circular reference.', $departmentId));
        $e->statusCode = 422;
        $e->translationKey = 'error.department_circular_reference';
        $e->translationParams = ['%departmentId%' => $departmentId];

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
