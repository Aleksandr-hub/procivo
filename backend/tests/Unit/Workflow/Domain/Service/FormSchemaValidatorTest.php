<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workflow\Domain\Service;

use App\Workflow\Domain\Service\FormSchemaValidator;
use App\Workflow\Domain\ValueObject\FieldValidationError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormSchemaValidatorTest extends TestCase
{
    private FormSchemaValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FormSchemaValidator();
    }

    // =====================
    // Required validation (COMP-02)
    // =====================

    #[Test]
    public function itReturnsErrorForMissingRequiredField(): void
    {
        $fields = [$this->makeField('name', 'text', ['required' => true])];
        $formData = [];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertInstanceOf(FieldValidationError::class, $errors[0]);
        self::assertSame('name', $errors[0]->field);
        self::assertSame('required', $errors[0]->rule);
    }

    #[Test]
    public function itPassesForOptionalMissingField(): void
    {
        $fields = [$this->makeField('name', 'text', ['required' => false])];
        $formData = [];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    #[Test]
    public function itReturnsErrorForEmptyStringOnRequiredField(): void
    {
        $fields = [$this->makeField('name', 'text', ['required' => true])];
        $formData = ['name' => ''];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('name', $errors[0]->field);
        self::assertSame('required', $errors[0]->rule);
    }

    // =====================
    // Type validation (COMP-02)
    // =====================

    #[Test]
    public function itReturnsErrorForInvalidNumberType(): void
    {
        $fields = [$this->makeField('amount', 'number')];
        $formData = ['amount' => 'not-a-number'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('amount', $errors[0]->field);
        self::assertSame('type', $errors[0]->rule);
    }

    #[Test]
    public function itPassesForValidNumberType(): void
    {
        $fields = [$this->makeField('amount', 'number')];
        $formData = ['amount' => 42];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    #[Test]
    public function itPassesForNumericStringAsNumber(): void
    {
        $fields = [$this->makeField('amount', 'number')];
        $formData = ['amount' => '42.5'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    #[Test]
    public function itReturnsErrorForInvalidDateType(): void
    {
        $fields = [$this->makeField('deadline', 'date')];
        $formData = ['deadline' => 'not-a-date'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('deadline', $errors[0]->field);
        self::assertSame('type', $errors[0]->rule);
    }

    #[Test]
    public function itPassesForValidDateType(): void
    {
        $fields = [$this->makeField('deadline', 'date')];
        $formData = ['deadline' => '2026-02-28'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    #[Test]
    public function itValidatesSelectFieldAgainstOptions(): void
    {
        $fields = [$this->makeField('priority', 'select', ['options' => ['a', 'b']])];
        $formData = ['priority' => 'c'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('priority', $errors[0]->field);
        self::assertSame('type', $errors[0]->rule);
    }

    #[Test]
    public function itPassesForValidSelectOption(): void
    {
        $fields = [$this->makeField('priority', 'select', ['options' => ['a', 'b']])];
        $formData = ['priority' => 'a'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    #[Test]
    public function itValidatesCheckboxAsBooleanish(): void
    {
        $fields = [$this->makeField('agree', 'checkbox')];
        $formData = ['agree' => 'not-boolean'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('agree', $errors[0]->field);
        self::assertSame('type', $errors[0]->rule);

        // Valid boolean-ish values should pass
        foreach ([true, false, 1, 0, '1', '0'] as $valid) {
            $validErrors = $this->validator->validate($fields, ['agree' => $valid]);
            self::assertSame([], $validErrors, \sprintf('Expected no errors for checkbox value: %s', var_export($valid, true)));
        }
    }

    // =====================
    // Constraint validation (COMP-02)
    // =====================

    #[Test]
    public function itReturnsErrorForNumberBelowMin(): void
    {
        $fields = [$this->makeField('amount', 'number', ['min' => 10])];
        $formData = ['amount' => 5];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('amount', $errors[0]->field);
        self::assertSame('min', $errors[0]->rule);
        self::assertSame(10, $errors[0]->params['min']);
        self::assertSame(5, $errors[0]->params['actual']);
    }

    #[Test]
    public function itReturnsErrorForNumberAboveMax(): void
    {
        $fields = [$this->makeField('amount', 'number', ['max' => 100])];
        $formData = ['amount' => 150];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('amount', $errors[0]->field);
        self::assertSame('max', $errors[0]->rule);
        self::assertSame(100, $errors[0]->params['max']);
        self::assertSame(150, $errors[0]->params['actual']);
    }

    #[Test]
    public function itReturnsErrorForStringBelowMinLength(): void
    {
        $fields = [$this->makeField('code', 'text', ['minLength' => 3])];
        $formData = ['code' => 'ab'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('code', $errors[0]->field);
        self::assertSame('minLength', $errors[0]->rule);
        self::assertSame(3, $errors[0]->params['minLength']);
        self::assertSame(2, $errors[0]->params['actual']);
    }

    #[Test]
    public function itReturnsErrorForStringAboveMaxLength(): void
    {
        $fields = [$this->makeField('code', 'text', ['maxLength' => 10])];
        $formData = ['code' => 'toolongstring'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('code', $errors[0]->field);
        self::assertSame('maxLength', $errors[0]->rule);
        self::assertSame(10, $errors[0]->params['maxLength']);
        self::assertSame(13, $errors[0]->params['actual']);
    }

    #[Test]
    public function itReturnsErrorForPatternMismatch(): void
    {
        $fields = [$this->makeField('code', 'text', ['pattern' => '^[A-Z]{2}-\d+$'])];
        $formData = ['code' => 'invalid'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('code', $errors[0]->field);
        self::assertSame('pattern', $errors[0]->rule);
    }

    #[Test]
    public function itPassesForPatternMatch(): void
    {
        $fields = [$this->makeField('code', 'text', ['pattern' => '^[A-Z]{2}-\d+$'])];
        $formData = ['code' => 'AB-123'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    // =====================
    // Field dependency validation (COMP-05)
    // =====================

    #[Test]
    public function itSkipsValidationForHiddenDependentField(): void
    {
        $fields = [
            $this->makeField('type', 'select', ['options' => ['normal', 'urgent']]),
            $this->makeField('urgency_reason', 'text', [
                'required' => true,
                'dependsOn' => ['field' => 'type', 'value' => 'urgent'],
            ]),
        ];
        $formData = ['type' => 'normal'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    #[Test]
    public function itValidatesDependentFieldWhenConditionMet(): void
    {
        $fields = [
            $this->makeField('type', 'select', ['options' => ['normal', 'urgent']]),
            $this->makeField('urgency_reason', 'text', [
                'required' => true,
                'dependsOn' => ['field' => 'type', 'value' => 'urgent'],
            ]),
        ];
        $formData = ['type' => 'urgent'];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('urgency_reason', $errors[0]->field);
        self::assertSame('required', $errors[0]->rule);
    }

    #[Test]
    public function itHandlesCascadingDependencies(): void
    {
        $fields = [
            $this->makeField('type', 'select', ['options' => ['normal', 'urgent']]),
            $this->makeField('urgency_level', 'select', [
                'required' => true,
                'options' => ['high', 'critical'],
                'dependsOn' => ['field' => 'type', 'value' => 'urgent'],
            ]),
            $this->makeField('escalation_contact', 'text', [
                'required' => true,
                'dependsOn' => ['field' => 'urgency_level', 'value' => 'critical'],
            ]),
        ];

        // When type is 'normal', both urgency_level and escalation_contact are hidden
        $formData = ['type' => 'normal'];
        $errors = $this->validator->validate($fields, $formData);
        self::assertSame([], $errors, 'Cascading deps: both dependent fields should be hidden when root condition not met');

        // When type is 'urgent' but urgency_level is not 'critical', escalation_contact is hidden
        $formData = ['type' => 'urgent', 'urgency_level' => 'high'];
        $errors = $this->validator->validate($fields, $formData);
        self::assertSame([], $errors, 'Cascading deps: escalation_contact should be hidden when urgency_level != critical');

        // When type is 'urgent' and urgency_level is 'critical', escalation_contact is required
        $formData = ['type' => 'urgent', 'urgency_level' => 'critical'];
        $errors = $this->validator->validate($fields, $formData);
        self::assertCount(1, $errors, 'Cascading deps: escalation_contact should be validated when full chain is met');
        self::assertSame('escalation_contact', $errors[0]->field);
    }

    // =====================
    // Edge cases
    // =====================

    #[Test]
    public function itReturnsEmptyArrayForValidData(): void
    {
        $fields = [
            $this->makeField('name', 'text', ['required' => true]),
            $this->makeField('amount', 'number', ['min' => 0, 'max' => 1000]),
        ];
        $formData = ['name' => 'Test', 'amount' => 500];

        $errors = $this->validator->validate($fields, $formData);

        self::assertSame([], $errors);
    }

    #[Test]
    public function itReturnsMultipleErrorsForMultipleInvalidFields(): void
    {
        $fields = [
            $this->makeField('name', 'text', ['required' => true]),
            $this->makeField('email', 'text', ['required' => true]),
        ];
        $formData = [];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(2, $errors);
        $errorFields = array_map(static fn (FieldValidationError $e): string => $e->field, $errors);
        self::assertContains('name', $errorFields);
        self::assertContains('email', $errorFields);
    }

    #[Test]
    public function itSkipsFurtherValidationAfterRequiredError(): void
    {
        // A required number field with min constraint -- when missing, only 'required' error, not 'min'
        $fields = [$this->makeField('amount', 'number', ['required' => true, 'min' => 10])];
        $formData = [];

        $errors = $this->validator->validate($fields, $formData);

        self::assertCount(1, $errors);
        self::assertSame('required', $errors[0]->rule);
    }

    // =====================
    // Helper
    // =====================

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function makeField(string $name, string $type, array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'label' => ucfirst($name),
            'type' => $type,
            'required' => false,
            'options' => [],
            'min' => null,
            'max' => null,
            'minLength' => null,
            'maxLength' => null,
            'pattern' => null,
            'dependsOn' => null,
        ], $overrides);
    }
}
