<?php

declare(strict_types=1);

namespace App\Workflow\Domain\ValueObject;

/**
 * Structured validation error for a single form field.
 *
 * Used by FormSchemaValidator to report validation failures
 * with enough detail for API consumers to display field-level errors.
 */
final readonly class FieldValidationError
{
    /**
     * @param string               $field   Field name that failed validation
     * @param string               $rule    Validation rule that failed (required, type, min, max, minLength, maxLength, pattern)
     * @param string               $message Human-readable error description
     * @param array<string, mixed> $params  Context parameters (e.g., ['min' => 5, 'actual' => 3])
     */
    public function __construct(
        public string $field,
        public string $rule,
        public string $message,
        public array $params = [],
    ) {
    }

    /**
     * @return array{field: string, rule: string, message: string, params: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'rule' => $this->rule,
            'message' => $this->message,
            'params' => $this->params,
        ];
    }
}
