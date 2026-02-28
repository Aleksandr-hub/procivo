<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\TranslatableExceptionInterface;
use App\Workflow\Domain\ValueObject\FieldValidationError;

final class FormValidationException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = '';
    /** @var array<string, string> */
    private array $translationParams = [];
    /** @var list<string> */
    private array $missingFields = [];
    /** @var list<FieldValidationError> */
    private array $validationErrors = [];

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

    /**
     * Create exception from structured validation errors.
     *
     * @param list<FieldValidationError> $errors
     */
    public static function validationFailed(array $errors): self
    {
        $fieldNames = array_map(
            static fn (FieldValidationError $error): string => $error->field,
            $errors,
        );
        $fieldList = implode(', ', array_unique($fieldNames));

        $e = new self(\sprintf('Form validation failed for fields: %s', $fieldList));
        $e->statusCode = 422;
        $e->translationKey = 'error.form_validation_failed';
        $e->translationParams = ['%fields%' => $fieldList];
        $e->validationErrors = $errors;

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

    /**
     * @return list<FieldValidationError>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Serialize validation errors for API response.
     *
     * @return list<array{field: string, rule: string, message: string, params: array<string, mixed>}>
     */
    public function getSerializedErrors(): array
    {
        return array_map(
            static fn (FieldValidationError $error): array => $error->toArray(),
            $this->validationErrors,
        );
    }
}
