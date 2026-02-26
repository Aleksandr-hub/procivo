<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;

final class FormValidationException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];
    /** @var list<string> */
    private array $missingFields = [];

    /**
     * @param list<string> $fields
     */
    public static function requiredFieldsMissing(array $fields): self
    {
        $fieldList = implode(', ', $fields);
        $e = new self(\sprintf('Required form fields missing: %s', $fieldList));
        $e->statusCode = 422;
        $e->translationKey = 'error.form_fields_required';
        $e->translationParams = ['%fields%' => $fieldList];
        $e->missingFields = $fields;

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

    /** @return list<string> */
    public function getMissingFields(): array
    {
        return $this->missingFields;
    }
}
