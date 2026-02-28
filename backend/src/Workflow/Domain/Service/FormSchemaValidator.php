<?php

declare(strict_types=1);

namespace App\Workflow\Domain\Service;

use App\Workflow\Domain\ValueObject\FieldValidationError;

/**
 * Validates form data against JSON schema definitions.
 *
 * Supports: required checks, type validation (text, number, date, select, checkbox, textarea, employee),
 * numeric constraints (min, max), string constraints (minLength, maxLength), regex patterns,
 * and field dependency resolution (single-level and cascading).
 */
final class FormSchemaValidator
{
    private const int MAX_DEPENDENCY_ITERATIONS = 10;

    private const array BOOLEAN_ISH_VALUES = [true, false, 1, 0, '1', '0'];

    /**
     * Validate form data against field definitions.
     *
     * @param list<array<string, mixed>> $fields   Form field definitions
     * @param array<string, mixed>       $formData Submitted data
     *
     * @return list<FieldValidationError> Empty array means valid
     */
    public function validate(array $fields, array $formData): array
    {
        $errors = [];
        $effectiveFields = $this->resolveFieldDependencies($fields, $formData);

        foreach ($effectiveFields as $field) {
            $fieldName = $field['name'];
            $value = $formData[$fieldName] ?? null;

            // Required check (only for visible + required fields)
            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                $errors[] = new FieldValidationError(
                    $fieldName,
                    'required',
                    \sprintf('Field "%s" is required', $fieldName),
                );
                continue; // Skip further validation for missing required fields
            }

            // Optional + empty = valid, skip type/constraint checks
            if ($value === null || $value === '') {
                continue;
            }

            // Type validation
            $typeError = $this->validateType($field, $value);
            if ($typeError !== null) {
                $errors[] = $typeError;
                continue;
            }

            // Constraint validation (min, max, minLength, maxLength, pattern)
            $constraintErrors = $this->validateConstraints($field, $value);
            foreach ($constraintErrors as $constraintError) {
                $errors[] = $constraintError;
            }
        }

        return $errors;
    }

    /**
     * Resolve field dependencies and return only effectively visible fields.
     *
     * Uses iterative resolution to handle cascading dependencies:
     * if field B depends on field A, and field C depends on field B,
     * then when A's condition is not met, both B and C are hidden.
     *
     * @param list<array<string, mixed>> $fields
     * @param array<string, mixed>       $formData
     *
     * @return list<array<string, mixed>>
     */
    private function resolveFieldDependencies(array $fields, array $formData): array
    {
        // Build a map of field name -> field definition for quick lookups
        $fieldMap = [];
        foreach ($fields as $field) {
            $fieldMap[$field['name']] = $field;
        }

        // Track which fields are visible (effective)
        $visible = [];
        foreach ($fields as $field) {
            $visible[$field['name']] = true; // Assume all visible initially
        }

        // Iterative resolution: handle cascading dependencies
        for ($i = 0; $i < self::MAX_DEPENDENCY_ITERATIONS; ++$i) {
            $changed = false;

            foreach ($fields as $field) {
                $fieldName = $field['name'];
                $dependsOn = $field['dependsOn'] ?? null;

                if ($dependsOn === null) {
                    continue; // No dependency, always visible
                }

                $dependencyField = $dependsOn['field'] ?? '';
                $dependencyValue = $dependsOn['value'] ?? null;

                // If the parent field is not visible, this field is also not visible
                if (!($visible[$dependencyField] ?? false)) {
                    if ($visible[$fieldName]) {
                        $visible[$fieldName] = false;
                        $changed = true;
                    }
                    continue;
                }

                // Check if the dependency condition is met
                $actualValue = $formData[$dependencyField] ?? null;
                $conditionMet = $actualValue === $dependencyValue
                    || (string) $actualValue === (string) $dependencyValue;

                $newVisibility = $conditionMet;
                if ($visible[$fieldName] !== $newVisibility) {
                    $visible[$fieldName] = $newVisibility;
                    $changed = true;
                }
            }

            if (!$changed) {
                break; // Stable state reached
            }
        }

        // Return only visible fields
        $effectiveFields = [];
        foreach ($fields as $field) {
            if ($visible[$field['name']]) {
                $effectiveFields[] = $field;
            }
        }

        return $effectiveFields;
    }

    /**
     * Validate field value type.
     *
     * @param array<string, mixed> $field
     */
    private function validateType(array $field, mixed $value): ?FieldValidationError
    {
        $fieldName = $field['name'];
        $type = $field['type'] ?? 'text';

        return match ($type) {
            'text', 'textarea', 'employee' => $this->validateStringType($fieldName, $value),
            'number' => $this->validateNumberType($fieldName, $value),
            'date' => $this->validateDateType($fieldName, $value),
            'select' => $this->validateSelectType($fieldName, $value, $field['options'] ?? []),
            'checkbox' => $this->validateCheckboxType($fieldName, $value),
            default => null, // Unknown types pass validation
        };
    }

    private function validateStringType(string $fieldName, mixed $value): ?FieldValidationError
    {
        if (!\is_string($value)) {
            return new FieldValidationError(
                $fieldName,
                'type',
                \sprintf('Field "%s" must be a string', $fieldName),
                ['expected' => 'string', 'actual' => get_debug_type($value)],
            );
        }

        return null;
    }

    private function validateNumberType(string $fieldName, mixed $value): ?FieldValidationError
    {
        if (!is_numeric($value)) {
            return new FieldValidationError(
                $fieldName,
                'type',
                \sprintf('Field "%s" must be a number', $fieldName),
                ['expected' => 'number', 'actual' => get_debug_type($value)],
            );
        }

        return null;
    }

    private function validateDateType(string $fieldName, mixed $value): ?FieldValidationError
    {
        if (!\is_string($value)) {
            return new FieldValidationError(
                $fieldName,
                'type',
                \sprintf('Field "%s" must be a valid date string', $fieldName),
                ['expected' => 'date', 'actual' => get_debug_type($value)],
            );
        }

        // Validate date format using DateTime parsing
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
            // Try general date parsing as fallback
            try {
                new \DateTimeImmutable($value);
            } catch (\Exception) {
                return new FieldValidationError(
                    $fieldName,
                    'type',
                    \sprintf('Field "%s" must be a valid date', $fieldName),
                    ['expected' => 'date', 'actual' => $value],
                );
            }
        }

        return null;
    }

    /**
     * @param list<string> $options
     */
    private function validateSelectType(string $fieldName, mixed $value, array $options): ?FieldValidationError
    {
        if (!\in_array($value, $options, true)) {
            return new FieldValidationError(
                $fieldName,
                'type',
                \sprintf('Field "%s" must be one of: %s', $fieldName, implode(', ', $options)),
                ['expected' => $options, 'actual' => $value],
            );
        }

        return null;
    }

    private function validateCheckboxType(string $fieldName, mixed $value): ?FieldValidationError
    {
        if (!\in_array($value, self::BOOLEAN_ISH_VALUES, true)) {
            return new FieldValidationError(
                $fieldName,
                'type',
                \sprintf('Field "%s" must be a boolean value', $fieldName),
                ['expected' => 'boolean', 'actual' => get_debug_type($value)],
            );
        }

        return null;
    }

    /**
     * Validate constraints (min, max, minLength, maxLength, pattern).
     *
     * @param array<string, mixed> $field
     *
     * @return list<FieldValidationError>
     */
    private function validateConstraints(array $field, mixed $value): array
    {
        $errors = [];
        $fieldName = $field['name'];

        // Numeric constraints
        if (isset($field['min']) && is_numeric($value)) {
            $numericValue = $value + 0; // Cast to int or float
            if ($numericValue < $field['min']) {
                $errors[] = new FieldValidationError(
                    $fieldName,
                    'min',
                    \sprintf('Field "%s" must be at least %s', $fieldName, $field['min']),
                    ['min' => $field['min'], 'actual' => $numericValue],
                );
            }
        }

        if (isset($field['max']) && is_numeric($value)) {
            $numericValue = $value + 0;
            if ($numericValue > $field['max']) {
                $errors[] = new FieldValidationError(
                    $fieldName,
                    'max',
                    \sprintf('Field "%s" must be at most %s', $fieldName, $field['max']),
                    ['max' => $field['max'], 'actual' => $numericValue],
                );
            }
        }

        // String length constraints
        if (isset($field['minLength']) && \is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $field['minLength']) {
                $errors[] = new FieldValidationError(
                    $fieldName,
                    'minLength',
                    \sprintf('Field "%s" must be at least %d characters', $fieldName, $field['minLength']),
                    ['minLength' => $field['minLength'], 'actual' => $length],
                );
            }
        }

        if (isset($field['maxLength']) && \is_string($value)) {
            $length = mb_strlen($value);
            if ($length > $field['maxLength']) {
                $errors[] = new FieldValidationError(
                    $fieldName,
                    'maxLength',
                    \sprintf('Field "%s" must be at most %d characters', $fieldName, $field['maxLength']),
                    ['maxLength' => $field['maxLength'], 'actual' => $length],
                );
            }
        }

        // Regex pattern constraint
        if (isset($field['pattern']) && \is_string($value)) {
            $pattern = '/' . str_replace('/', '\/', $field['pattern']) . '/';
            if (!preg_match($pattern, $value)) {
                $errors[] = new FieldValidationError(
                    $fieldName,
                    'pattern',
                    \sprintf('Field "%s" does not match required pattern', $fieldName),
                    ['pattern' => $field['pattern'], 'actual' => $value],
                );
            }
        }

        return $errors;
    }
}
