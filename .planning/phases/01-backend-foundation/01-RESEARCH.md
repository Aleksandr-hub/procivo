# Phase 1: Backend Foundation - Research

**Researched:** 2026-02-28
**Domain:** Symfony ExpressionLanguage, process variable namespacing, form schema validation
**Confidence:** HIGH

## Summary

Phase 1 establishes three backend pillars: (1) safe XOR gateway condition evaluation against process variables, (2) namespaced variable merging that prevents key collisions between workflow stages, and (3) a FormSchemaValidator for structured field validation. All three areas build on existing codebase infrastructure -- the ExpressionEvaluator, ProcessInstance.mergeVariables, and the inline validation in ExecuteTaskActionHandler.

The codebase already has Symfony ExpressionLanguage (`symfony/expression-language: 8.0.*`) installed and a thin ExpressionEvaluator wrapper. The current wrapper catches SyntaxError but NOT runtime errors (TypeError, undefined variable warnings). Templates already use conditions like `decision == 'Done'` referencing flat variable names. The current `applyVariablesMerged` does a flat `array_merge` -- no namespacing. The current `validateFormData` in ExecuteTaskActionHandler only checks `required` fields -- no type, min/max, regex, or dependency validation.

**Primary recommendation:** Enhance ExpressionEvaluator with try/catch for all Throwable + structured logging, implement dual-layer variable namespacing (namespaced storage + flat aliases for expression ergonomics), and extract FormSchemaValidator as a standalone domain service with a rule-per-type architecture.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **Expression syntax**: Simple comparisons only: ==, !=, >, <, >=, <=, in, not in. Logical operators: and, or, not. Null handling: null coalescing, null-safe access. NO custom functions, NO array operations, NO matches() -- keep it simple for designers. Covers 95% of BPM scenarios (status checks, amount thresholds, role matching)
- **Default branch behavior**: Designer can mark one outgoing transition as "default/else" on XOR gateway. Default branch is NOT required -- if no default set and no condition matches, process stops with error. Validator does NOT enforce default branch at design time (optional but recommended)
- **Expression error handling**: Undefined variable -> log structured warning, treat condition as false, fall through to default branch. Type mismatch -> same: warning + false. Process never silently mis-routes -- either a condition matches, default is taken, or explicit error. No process freeze on expression errors -- graceful degradation
- **Design-time validation**: Syntax check only when saving process definition -- verify expression parses without errors. No semantic validation (variables are unknown at design time). Invalid syntax -> save blocked with error message pointing to the problematic expression

### Claude's Discretion
- Variable namespace format -- how to structure nodeId-based namespacing and how expressions reference namespaced variables
- Validation error format -- structure of validation error responses for downstream frontend consumption
- Field dependency model complexity -- simple show/hide vs cascading chains
- FormSchemaValidator internal design -- class structure, rule registry approach

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| GATE-01 | ExpressionEvaluator integrated with XOR gateway -- evaluates conditions against ProcessInstance.variables | ExpressionEvaluator already exists and is wired into handleExclusiveGateway. Enhancement needed: runtime error handling (TypeError/\Throwable catch), structured logging, variable preparation |
| GATE-02 | Full Symfony ExpressionLanguage support (operators, functions, arrays, null-coalescing) | **Scoped down by user decision**: simple comparisons only (==, !=, >, <, >=, <=, in, not in), logical (and, or, not), null (??, ?.). NO matches(), NO custom functions. Symfony EL supports all of these natively |
| GATE-03 | Undefined variables in expressions log warnings and evaluate safely (no silent mis-routing) | ExpressionEvaluator.evaluate currently catches SyntaxError only. Must catch \Throwable, log structured warning with expression/variables/nodeId context, return false. Psr\LoggerInterface available via monolog-bundle |
| GATE-04 | Default/else branch on XOR gateway when no condition matches | Already implemented in handleExclusiveGateway via `default_transition_id` in node config. Needs verification/hardening: ensure exception message is clear when no default + no match |
| COMP-02 | Backend validates formData against form_schema (required, type, min/max, regex patterns) | Current validation is required-only (ExecuteTaskActionHandler.validateFormData). Must extract to FormSchemaValidator with type checking, numeric min/max, string min/max length, regex pattern matching |
| COMP-03 | Backend merges validated formData into ProcessInstance.variables with namespace prefix to prevent collisions | Current mergeVariables does flat array_merge. Must implement namespaced storage format + flat alias layer for expression ergonomics |
| COMP-05 | Field dependency validation -- show/require field X only when field Y has specific value | No current implementation. FormSchemaValidator must evaluate `dependsOn` conditions during validation to determine effective required/visible state |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| symfony/expression-language | 8.0.* | Expression parsing, linting, evaluation | Already installed. Provides null-coalescing (7.2+), null-safe (?.), lint() with Parser flags, AST inspection |
| symfony/validator | 8.0.* | Constraint-based validation framework | Already installed. Provides Collection, Type, Length, Regex, NotBlank constraints for programmatic array validation |
| symfony/monolog-bundle | ^4.0 | PSR-3 structured logging | Already installed. Provides LoggerInterface injection for structured warning logs |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHPUnit | ^13.0 | Unit testing | Already installed. All new domain services must have unit tests |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Symfony Validator for FormSchemaValidator | Custom validation loop | Custom loop is simpler for this use case since form schema is dynamic JSON, not a typed DTO. Symfony Validator Collection constraint requires building constraints programmatically from schema. **Recommendation: custom validation loop** -- more readable, fewer abstraction layers, and the form schema structure is simple enough |
| Symfony ExpressionLanguage for gateway conditions | Custom parser / Symfony Workflow guards | EL is already integrated and covers all needed operators. Custom parser is unnecessary work |

## Architecture Patterns

### Current Codebase Structure (Workflow module)
```
backend/src/Workflow/
├── Domain/
│   ├── Entity/          # ProcessInstance (event-sourced), Node, Transition, Token, etc.
│   ├── Event/           # VariablesMergedEvent, GatewayEvaluatedEvent, etc.
│   ├── Exception/       # FormValidationException, WorkflowExecutionException
│   ├── Service/         # ExpressionEvaluator, ProcessGraph, WorkflowEngine, ProcessGraphValidator
│   └── ValueObject/     # NodeId, TokenId, ConditionExpression, etc.
├── Application/
│   ├── Command/         # ExecuteTaskAction, PublishProcessDefinition, etc.
│   ├── EventHandler/    # OnTaskNodeActivated
│   ├── Service/         # FormFieldCollector
│   └── Query/           # GetProcessInstanceGraph, etc.
├── Infrastructure/
│   ├── EventStore/      # EventSerializer
│   └── Repository/      # Doctrine repositories
└── Presentation/
    └── Controller/      # ProcessDefinitionController, ProcessInstanceController
```

### Pattern 1: Namespaced Variable Storage with Flat Aliases
**What:** Store form data namespaced by nodeId in `ProcessInstance.variables`, but also write flat aliases for the most recently submitted values -- so expressions remain simple (`decision == 'Done'` instead of `stages.node_abc123.decision == 'Done'`).

**When to use:** Every time formData is merged into process variables.

**Design:**
```php
// Given: nodeId = "node_review", actionKey = "approve", formData = ["decision" => "Approved", "comment" => "LGTM"]

// Namespaced storage (prevents collisions):
$namespaced = [
    'stages' => [
        'node_review' => [
            'approve' => [
                'decision' => 'Approved',
                'comment' => 'LGTM',
            ],
        ],
    ],
];

// Flat aliases (for expression ergonomics -- last-write-wins):
$flat = [
    'decision' => 'Approved',
    'comment' => 'LGTM',
];

// Final merge into ProcessInstance.variables:
// 1. Deep-merge namespaced into existing variables
// 2. Overwrite flat aliases at root level
```

**Why this pattern:**
- Expressions in templates already use flat names: `decision == 'Done'`, `verification == 'OK'`
- Breaking all existing template expressions to use namespaced paths would be poor DX
- Namespaced copy preserves full history and prevents collisions
- Flat alias is "last writer wins" -- natural for linear process flows
- If a process has two stages that both produce `decision`, the XOR gateway after each one sees the correct value because execution is sequential
- For parallel gateways (future), the namespaced copy is the authoritative source

**Implementation location:** `ProcessInstance::mergeVariables` -- modify the `applyVariablesMerged` handler.

### Pattern 2: FormSchemaValidator as Domain Service
**What:** A standalone service that validates form data against a JSON schema definition. Not a Symfony Validator constraint -- a custom domain service with a simple rule dispatch.

**When to use:** Called from `ExecuteTaskActionHandler` before merging variables.

**Design:**
```php
final class FormSchemaValidator
{
    /**
     * @param list<array<string, mixed>> $fields   Form field definitions
     * @param array<string, mixed>       $formData Submitted data
     * @return list<FieldValidationError>          Empty = valid
     */
    public function validate(array $fields, array $formData): array
    {
        $errors = [];
        $effectiveFields = $this->resolveFieldDependencies($fields, $formData);

        foreach ($effectiveFields as $field) {
            $fieldName = $field['name'];
            $value = $formData[$fieldName] ?? null;

            // Required check (only for visible + required fields)
            if ($field['required'] && ($value === null || $value === '')) {
                $errors[] = new FieldValidationError($fieldName, 'required', 'Field is required');
                continue; // skip further validation for missing required fields
            }

            if ($value === null || $value === '') {
                continue; // optional + empty = valid
            }

            // Type validation
            $typeError = $this->validateType($field['type'], $value);
            if ($typeError !== null) {
                $errors[] = $typeError;
                continue;
            }

            // Constraint validation (min, max, minLength, maxLength, pattern)
            $errors = [...$errors, ...$this->validateConstraints($field, $value)];
        }

        return $errors;
    }
}
```

### Pattern 3: ExpressionEvaluator Enhancement with Structured Logging
**What:** Wrap evaluation in \Throwable catch, inject LoggerInterface, return evaluation result with metadata.

**Design:**
```php
final class ExpressionEvaluator
{
    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage,
        private readonly LoggerInterface $logger,
    ) {}

    public function evaluate(string $expression, array $variables = []): mixed
    {
        try {
            return $this->expressionLanguage->evaluate($expression, $variables);
        } catch (\Throwable $e) {
            $this->logger->warning('Expression evaluation failed', [
                'expression' => $expression,
                'error' => $e->getMessage(),
                'error_class' => $e::class,
                'variable_keys' => array_keys($variables),
            ]);
            return false;
        }
    }

    /**
     * Validate expression syntax at design time.
     * @throws SyntaxError if expression is syntactically invalid
     */
    public function lint(string $expression): void
    {
        $this->expressionLanguage->lint(
            $expression,
            [],
            Parser::IGNORE_UNKNOWN_VARIABLES,
        );
    }
}
```

### Anti-Patterns to Avoid
- **Exposing full Symfony EL to process designers:** User decision locks this down -- simple operators only. Even though we use Symfony EL internally, the lint() at publish time can catch dangerous constructs (constant(), enum() calls) or we simply don't document them. Since expressions come from organization admins (not untrusted users), the security risk is low, but keeping the supported syntax simple reduces confusion.
- **Deep namespacing only (no flat aliases):** Would break all existing template expressions (`decision == 'Done'`). Every expression would need to reference `stages.node_123.approve.decision` -- terrible DX.
- **Flat variables only (no namespacing):** The current approach. Works for simple linear processes but collisions are inevitable once two stages use the same field name (e.g., two review stages both produce `decision`).
- **Using Symfony Validator Collection constraint for dynamic form schema:** Overengineered for this case. We'd need to programmatically build Constraint trees from JSON schema definitions. A custom validator loop is clearer and more maintainable.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Expression parsing & evaluation | Custom expression parser | Symfony ExpressionLanguage (already installed) | Handles operator precedence, type coercion, null-safe access, caching -- 1000+ edge cases |
| Expression syntax validation | Custom regex-based syntax checker | `ExpressionLanguage::lint()` with `Parser::IGNORE_UNKNOWN_VARIABLES` | Parser validates full AST, catches balanced parens, unknown operators, malformed strings |
| Structured logging | Custom file-based logging | Psr\LoggerInterface via monolog-bundle (already installed) | JSON-structured logs, channel routing, log level filtering |

**Key insight:** The expression language is the one area where hand-rolling is most dangerous. Symfony EL handles dozens of edge cases (operator precedence, string escaping, type coercion in comparisons) that a custom solution would get wrong.

## Common Pitfalls

### Pitfall 1: Runtime TypeError in ExpressionLanguage
**What goes wrong:** Expression like `amount > 1000` throws TypeError if `amount` is a string `"1500"` (since Symfony 7.0, `in` uses strict comparison; other operators may throw on unexpected types too).
**Why it happens:** Process variables come from form submissions as strings. ExpressionLanguage doesn't auto-coerce types.
**How to avoid:** Wrap evaluate() in \Throwable catch (not just SyntaxError). Log warning and return false. The user decision explicitly says: type mismatch -> warning + false.
**Warning signs:** Tests that pass with integer variables but fail with string values.

### Pitfall 2: Undefined Variables at Evaluation Time
**What goes wrong:** Expression `decision == 'Approved'` when `decision` key is not in variables array. PHP notice or TypeError depending on how EL resolves it.
**Why it happens:** The variable hasn't been submitted yet (process hasn't reached that stage), or a typo in the expression.
**How to avoid:** The evaluate() method must catch all errors. The `lint()` method uses `Parser::IGNORE_UNKNOWN_VARIABLES` flag -- correct, because at design time we don't know which variables will exist. At runtime, the catch-all in evaluate() handles the missing variable gracefully.
**Warning signs:** Expressions that work in simple test cases but fail in production with complex process flows.

### Pitfall 3: Variable Collision in Parallel Flows
**What goes wrong:** Two parallel branches both set `status` variable, then merge -- one value silently overwrites the other.
**Why it happens:** Flat `array_merge` has no concept of provenance.
**How to avoid:** Namespaced storage (`stages.{nodeId}.{actionKey}.{fieldName}`) preserves both values. Flat alias is "last writer wins" which is fine for sequential flows and acceptable for parallel (the namespaced copy is authoritative).
**Warning signs:** Incorrect variable values after parallel gateway merge.

### Pitfall 4: Deep Merge Overwrites Arrays
**What goes wrong:** PHP `array_merge` replaces nested arrays entirely instead of merging them recursively. Second stage's variables wipe out first stage's namespaced data.
**Why it happens:** `array_merge(['stages' => ['a' => ...]], ['stages' => ['b' => ...]])` replaces the entire `stages` key.
**How to avoid:** Use `array_replace_recursive` or a custom deep-merge for the namespaced portion.
**Warning signs:** After second stage completion, first stage's data is missing from process variables.

### Pitfall 5: FormSchemaValidator Not Respecting Field Dependencies
**What goes wrong:** A field marked `required` with `dependsOn: {field: 'type', value: 'urgent'}` is always required, even when `type != 'urgent'`.
**Why it happens:** Validation doesn't check dependency conditions before applying required rule.
**How to avoid:** Resolve field dependencies first (determine effective visibility/required state), then validate only effective fields.
**Warning signs:** Users can't submit forms because hidden dependent fields fail validation.

### Pitfall 6: Expression Syntax Check Blocking Invalid but Harmless Expressions
**What goes wrong:** lint() throws SyntaxError for expressions that use unknown functions (e.g., a user types `upper(status)` thinking it works).
**Why it happens:** lint() validates syntax including function names by default.
**How to avoid:** Use `Parser::IGNORE_UNKNOWN_FUNCTIONS` if needed, or provide clear error messages indicating which functions are supported.
**Warning signs:** Process designers can't save definitions with valid-looking expressions.

## Code Examples

### Current: ExpressionEvaluator (as-is)
```php
// Source: backend/src/Workflow/Domain/Service/ExpressionEvaluator.php
final class ExpressionEvaluator
{
    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function evaluate(string $expression, array $variables = []): mixed
    {
        try {
            return $this->expressionLanguage->evaluate($expression, $variables);
        } catch (SyntaxError) {
            return false;
        }
    }
}
```

### Current: Variable Merge (flat, no namespacing)
```php
// Source: backend/src/Workflow/Domain/Entity/ProcessInstance.php:539
private function applyVariablesMerged(VariablesMergedEvent $event): void
{
    $this->variables = array_merge($this->variables, $event->mergedData);
}
```

### Current: Inline Validation (required only)
```php
// Source: backend/src/Workflow/Application/Command/ExecuteTaskAction/ExecuteTaskActionHandler.php:83-101
private function validateFormData(array $formFields, array $formData): void
{
    $missingFields = [];
    foreach ($formFields as $field) {
        $isRequired = (bool) ($field['required'] ?? false);
        $fieldKey = \is_string($field['name'] ?? null) ? $field['name'] : '';

        if ($isRequired && '' !== $fieldKey) {
            $value = $formData[$fieldKey] ?? null;
            if (null === $value || '' === $value) {
                $missingFields[] = $fieldKey;
            }
        }
    }

    if ([] !== $missingFields) {
        throw FormValidationException::requiredFieldsMissing($missingFields);
    }
}
```

### Current: Template Expressions (flat variable references)
```typescript
// Source: frontend/src/modules/workflow/data/process-templates.ts
{ sourceIndex: 3, targetIndex: 4, nameKey: 'Done', condition_expression: "decision == 'Done'" },
{ sourceIndex: 3, targetIndex: 1, nameKey: 'Rework', condition_expression: "decision == 'Rework'" },
{ sourceIndex: 3, targetIndex: 5, nameKey: 'Not relevant', condition_expression: "decision == 'Not relevant'" },
```

### Proposed: FieldValidationError Value Object
```php
final readonly class FieldValidationError
{
    public function __construct(
        public string $field,
        public string $rule,       // 'required', 'type', 'min', 'max', 'minLength', 'maxLength', 'pattern'
        public string $message,
        public array $params = [],  // e.g., ['min' => 5, 'actual' => 3]
    ) {}

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
```

### Proposed: FormFieldDefinition Expected Shape
```php
// Based on frontend FormFieldDefinition type + extensions for validation
[
    'name' => 'amount',               // field key in formData
    'label' => 'Invoice Amount',       // display label
    'type' => 'number',                // text|number|date|select|checkbox|textarea|employee
    'required' => true,
    'options' => ['opt1', 'opt2'],     // for select type
    'min' => 0,                        // for number type
    'max' => 1000000,                  // for number type
    'minLength' => null,               // for text/textarea type
    'maxLength' => 500,                // for text/textarea type
    'pattern' => '^[A-Z]{2}-\\d+$',   // regex for text type
    'dependsOn' => [                   // field dependency (COMP-05)
        'field' => 'type',
        'value' => 'urgent',
        // when type == 'urgent', this field becomes visible/required
    ],
]
```

### Proposed: Symfony EL lint() for Design-Time Validation
```php
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Parser;
use Symfony\Component\ExpressionLanguage\SyntaxError;

// At publish time, validate each transition's condition_expression:
$el = new ExpressionLanguage();
try {
    $el->lint($expression, [], Parser::IGNORE_UNKNOWN_VARIABLES);
} catch (SyntaxError $e) {
    // Block publish, return error to user
    $errors[] = sprintf(
        'Transition "%s": invalid expression syntax — %s',
        $transitionName,
        $e->getMessage()
    );
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `in` uses loose comparison | `in` uses strict comparison | Symfony 7.0 | Comparing `"5" in [5]` now returns false. Process variable types must match |
| No null-coalescing in EL | `??` operator supported | Symfony 6.2 | Expressions can safely handle missing keys: `status ?? 'pending'` |
| No null-safe in EL | `?.` operator supported | Symfony 6.1 | Safe object property access without null checks |
| No lint flags | `Parser::IGNORE_UNKNOWN_VARIABLES` | Symfony 7.1 | Can validate syntax without knowing runtime variables |
| No comment syntax | `/* comment */` in expressions | Symfony 7.2 | Process designers can annotate complex expressions |

**Key for this phase:** The `??` (null-coalescing) operator (available since Symfony 6.2, included in 8.0) is the user's preferred way to handle undefined variables in expressions. Designers can write `decision ?? 'pending' == 'Approved'` to safely default missing variables. However, even without `??`, the enhanced ExpressionEvaluator catch-all ensures graceful degradation.

## Open Questions

1. **Variable namespace format: separator character**
   - What we know: nodeIds in the codebase use format like "node_abc123" (UUID-based). Using dot notation `stages.node_abc123.approve.decision` is natural for nested access.
   - What's unclear: Should the `stages` key be configurable or hardcoded? Should actionKey always be included even when there's only one outgoing transition?
   - Recommendation: Hardcode `stages` prefix. Always include actionKey for consistency -- when single transition, use the transition's action_key or fallback to `_default`. This keeps the structure predictable.

2. **Expression security: should we restrict which EL features are available?**
   - What we know: User decision says "NO custom functions, NO array operations, NO matches()". Symfony EL has constant(), enum(), min(), max() built-in, and supports matches, contains, starts with, ends with operators.
   - What's unclear: Should we actively block these at lint time, or just not document them? Active blocking would require AST inspection after parsing.
   - Recommendation: **Don't actively block** -- it's overengineering for a pet project where process designers are trusted organization admins. The lint() at publish time catches syntax errors. Document only the supported subset in the UI/help text. If a designer uses `matches` and it works, no harm done.

3. **FormValidationException enhancement: backwards compatibility**
   - What we know: Current FormValidationException.requiredFieldsMissing() returns a list of field names. New validation needs to return structured errors (field + rule + message + params).
   - What's unclear: Should we add new factory methods or change the existing one?
   - Recommendation: Add new `static validationFailed(array $errors)` factory method. Keep `requiredFieldsMissing` for backwards compatibility but mark as deprecated internally. The new method accepts `list<FieldValidationError>`.

4. **Field dependency depth: simple show/hide vs cascading chains**
   - What we know: COMP-05 requires "show/require field X only when field Y has specific value".
   - What's unclear: Should we support chains like "field C depends on field B which depends on field A"?
   - Recommendation: Start with single-level dependencies (field depends on one other field). If field's dependency target is itself not visible (due to its own dependency), treat the dependent field as not visible either. This handles cascading without explicit multi-level configuration. Implementation: iterative resolution with cycle detection (max 10 iterations).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 13.0 |
| Config file | backend/phpunit.dist.xml |
| Quick run command | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow --testdox` |
| Full suite command | `cd backend && ./vendor/bin/phpunit` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| GATE-01 | ExpressionEvaluator evaluates conditions against variables | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow/Domain/Service/ExpressionEvaluatorTest.php -x` | No -- Wave 0 |
| GATE-02 | Supported operators work correctly (==, !=, >, <, in, not in, and, or, not, ??, ?.) | unit | same as GATE-01 | No -- Wave 0 |
| GATE-03 | Undefined variables log warning and return false | unit | same as GATE-01 | No -- Wave 0 |
| GATE-04 | Default branch taken when no condition matches | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow/Domain/Service/WorkflowEngineTest.php -x` | No -- Wave 0 |
| COMP-02 | FormSchemaValidator validates required, type, min/max, regex | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow/Domain/Service/FormSchemaValidatorTest.php -x` | No -- Wave 0 |
| COMP-03 | mergeVariables namespaces by nodeId, writes flat aliases | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow/Domain/Entity/ProcessInstanceTest.php -x` | No -- Wave 0 |
| COMP-05 | Field dependency validation (conditional required/visible) | unit | same as COMP-02 | No -- Wave 0 |

### Sampling Rate
- **Per task commit:** `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow --testdox`
- **Per wave merge:** `cd backend && ./vendor/bin/phpunit`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/Workflow/Domain/Service/ExpressionEvaluatorTest.php` -- covers GATE-01, GATE-02, GATE-03
- [ ] `tests/Unit/Workflow/Domain/Service/WorkflowEngineTest.php` -- covers GATE-04 (handleExclusiveGateway)
- [ ] `tests/Unit/Workflow/Domain/Service/FormSchemaValidatorTest.php` -- covers COMP-02, COMP-05
- [ ] `tests/Unit/Workflow/Domain/Entity/ProcessInstanceTest.php` -- covers COMP-03 (mergeVariables + namespacing)

## Sources

### Primary (HIGH confidence)
- Codebase inspection: ExpressionEvaluator.php, WorkflowEngine.php, ProcessInstance.php, ExecuteTaskActionHandler.php, FormFieldCollector.php, ProcessGraph.php, ProcessGraphValidator.php, FormValidationException.php -- all read directly from `/Users/leleka/Projects/procivo/backend/src/`
- [Symfony ExpressionLanguage Syntax Reference](https://symfony.com/doc/current/reference/formats/expression_language.html) -- full operator list, null-safe, null-coalescing, ternary
- [Symfony ExpressionLanguage Component Docs](https://symfony.com/doc/current/components/expression_language.html) -- lint(), Parser::IGNORE_UNKNOWN_VARIABLES, evaluate(), compile()
- [Symfony Validator: Raw Values](https://symfony.com/doc/current/validation/raw_values.html) -- Collection constraint for programmatic array validation
- [Symfony expression-language CHANGELOG.md (7.2)](https://github.com/symfony/expression-language/blob/7.2/CHANGELOG.md) -- null-coalescing, comments, bitwise, xor, lint flags

### Secondary (MEDIUM confidence)
- [Symfony Issue #53547: Handle all expression errors](https://github.com/symfony/symfony/issues/53547) -- confirms evaluate() does NOT catch TypeError/RuntimeException, only SyntaxError. Issue was stalled/closed without fix, confirming we must implement our own catch-all
- [Symfony Issue #59966: Nullsafe for array access](https://github.com/symfony/symfony/issues/59966) -- confirms `?.` does NOT work for array access, only object property/method access
- [bpmn-io/form-js FORM_SCHEMA.md](https://github.com/bpmn-io/form-js/blob/develop/docs/FORM_SCHEMA.md) -- industry-standard BPMN form schema structure for reference

### Tertiary (LOW confidence)
- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all libraries already installed and used in codebase
- Architecture: HIGH -- patterns derived from reading actual codebase code, not theoretical. Namespacing design validated against existing template expressions
- Pitfalls: HIGH -- runtime TypeError and undefined variable issues confirmed by Symfony GitHub issues
- Validation: HIGH -- field types and schema structure already defined in frontend TypeScript types

**Research date:** 2026-02-28
**Valid until:** 2026-03-28 (stable domain, no fast-moving dependencies)
