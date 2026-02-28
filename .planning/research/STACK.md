# Stack Research

**Domain:** BPM Workflow-Task Integration (Procivo)
**Researched:** 2026-02-28
**Confidence:** HIGH — core stack already installed and verified; recommendations are additive or pattern-based, verified against official Symfony 8 and Vue 3 docs.

---

## Context: What Is Already Installed

This is a subsequent-milestone research. The stack is fixed. The question is: what patterns, libraries, and tools to USE from the existing stack for the new features.

**Backend (already in composer.json):**
- `symfony/expression-language: 8.0.*` — already installed
- `symfony/validator: 8.0.*` — already installed (via framework-bundle)
- `symfony/workflow: 8.0.*` — already installed
- `doctrine/orm: ^3.6` with PostgreSQL — `json` type used for JSONB columns
- `symfony/messenger: 8.0.*` — 3-bus CQRS setup (command.bus, query.bus, event.bus)

**Frontend (already in package.json):**
- `primevue: ^4.5.4` + `@primevue/themes: ^4.5.4`
- `zod: ^4.3.6` — schema validation library
- `pinia: ^3.0.4` — state management
- `vue-router: ^5.0.2`
- `vue-i18n: ^12.0.0-alpha.3`
- `axios: ^1.13.5`

**What is already built for this milestone:**
- `ExpressionEvaluator` service using `symfony/expression-language` — evaluates gateway conditions
- `WorkflowEngine.executeAction()` — handles action-based task completion
- `FormFieldCollector` service — aggregates shared + transition-specific fields
- `ExecuteTaskActionHandler` — validates required fields, merges formData into ProcessInstance.variables
- `GetTaskWorkflowContextHandler` — returns form_schema with shared_fields + actions
- `AssignmentResolver` service — resolves by_role / by_department strategies
- `DynamicFormField.vue` — renders text, number, date, select, checkbox, textarea, employee fields
- `ActionFormDialog.vue` — dialog with shared + action-specific field rendering + client validation
- `ClaimTask` / `UnclaimTask` commands (new, untracked)

---

## Recommended Stack

### Core Backend Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| `symfony/expression-language` | 8.0.* | XOR/Inclusive gateway condition evaluation | Already installed. Native sandbox — expressions can only access explicitly provided variables. Supports `==`, `!=`, `>`, `<`, `>=`, `<=`, `in`, `not in`, `contains`, `starts with`, `ends with`, `matches` (regex), `and`/`or`/`not`, ternary, null-coalescing `??`, null-safe `?.`. Full BPM expression needs covered without exposing PHP execution. Verified against official Symfony 8 docs. [HIGH confidence] |
| `symfony/validator` | 8.0.* | Dynamic form field validation on CompleteTask | Already installed. Use `Assert\Collection` built programmatically from a form field schema array — supports `Required`, `Optional`, `NotBlank`, `Type`, `Length`, `Range`, `Regex`, `Email`. The pattern: build `Assert\Collection(['fields' => $fields])` from `formFields[]` schema, call `$validator->validate($data, $constraint)`. No additional library needed. Verified against official Symfony 8 docs. [HIGH confidence] |
| Doctrine ORM `json` type | 3.6 | Store form schemas, process variables as JSONB in PostgreSQL | Already in use (`form_fields`, `config`, `nodesSnapshot`). Doctrine `json` type maps to PostgreSQL `jsonb` — stored as binary JSON with full indexing capability. Use `json` type in XML mapping for `form_schema` on Task, `variables` on ProcessInstance. [HIGH confidence] |
| `symfony/messenger` | 8.0.* | CQRS command/query/event routing | Already configured with 3 buses. Pattern: `ExecuteTaskActionCommand` → command.bus, `GetTaskWorkflowContextQuery` → query.bus, `TaskNodeActivatedEvent` → event.bus. Use `#[AsMessageHandler(bus: 'command.bus')]`. Already proven in codebase. [HIGH confidence] |

### Core Frontend Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| PrimeVue 4 components | 4.5.4 | Dynamic form field rendering | Already installed. Use `InputText`, `InputNumber`, `DatePicker`, `Select`, `Checkbox`, `Textarea`, `Dialog` — all used in `DynamicFormField.vue` and `ActionFormDialog.vue`. No new UI library. `Dialog` with `modal` prop for `ActionFormDialog`. [HIGH confidence] |
| Zod 4 | 4.3.6 | Frontend schema validation for dynamic form fields | Already installed. Build schema dynamically from `FormFieldDefinition[]`: `z.object({...})` where each field maps to a Zod type (`z.string()`, `z.number()`, `z.coerce.date()`, etc.). Zod 4.3.x added `z.fromJSONSchema()` for converting JSON Schema to Zod — useful if backend starts returning JSON Schema format. Use `.optional()` vs `.min(1)` for required vs optional string fields. [HIGH confidence] |
| Pinia 3 | 3.0.4 | Task store — form schema caching, action submission state | Already installed. Pattern: `task.store.ts` fetches `TaskDetailDTO` including `workflow_context.form_schema`, stores locally. Action submission: `submittingAction: boolean` flag + `executeAction(payload)` action. [HIGH confidence] |

### Supporting Backend Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `symfony/expression-language` ExpressionFunctionProviderInterface | 8.0.* | Custom functions for BPM expressions | When gateway conditions need domain functions like `hasRole()`, `daysSince()`, `inList()`. Create `BpmExpressionProvider implements ExpressionFunctionProviderInterface` and register via constructor. Only needed if base operators are insufficient — for this milestone, base operators (`==`, `in`, `and`/`or`) cover all cases. [HIGH confidence] |
| `symfony/expression-language` caching | 8.0.* | Cache compiled expressions for performance | When process definitions have many gateways executing frequently. Use `RedisAdapter` (already have Redis 8 in stack). Pass to `new ExpressionLanguage($cache)`. LOW priority for pet project pace — defer until performance becomes an issue. [MEDIUM confidence] |
| `Assert\Collection` (symfony/validator) | 8.0.* | Dynamic form data validation | Primary pattern for validating `POST /tasks/{id}/complete` body. Build `Assert\Collection(['fields' => $constraintMap])` programmatically from `FormFieldDefinition[]`. Use `allowExtraFields: true` (user may submit additional data), `allowMissingFields: false` for required fields. Verified against Symfony 8 docs. [HIGH confidence] |

### Supporting Frontend Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `zod` (dynamic schema building) | 4.3.6 | Build validation schema from API-returned `FormFieldDefinition[]` | In `ActionFormDialog.vue` and `TaskDetailContent.vue`. Build `z.object()` dynamically: iterate `formField[]`, map `type` to Zod primitive, apply `.min(1)` for required text, `.nullable()` for optional. Call `schema.safeParse(formData)` before emitting submit event. Already installed — zero-cost addition. [HIGH confidence] |
| VeeValidate | NOT recommended | Form validation library | Do NOT add — overkill given existing `DynamicFormField.vue` + manual validation pattern. The existing approach (iterate fields, check required, collect errors) is 30 lines and works. VeeValidate adds ~40KB bundle, composable complexity, and PrimeVue integration friction. Use Zod `safeParse` instead for type-safe error messages. [HIGH confidence] |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| PHPStan 2.1 | Static analysis | Already configured. Add `@param list<array<string, mixed>> $formFields` docblocks to `FormFieldCollector` and `validateFormData` — PHPStan will catch mixed-type array access. |
| PHP CS Fixer 3.94 | Code style | Already configured. All new handlers/services follow existing camelCase test method style. |
| `symfony/maker-bundle` | Scaffolding | Already installed. Use `make:migration` after adding `form_schema` JSONB column to `task_manager_tasks`. |
| Vue DevTools 8 | Frontend debugging | Already in devDependencies (`vite-plugin-vue-devtools`). Enable to inspect Pinia store state during form submission debugging. |

---

## Installation

No new packages required for the core milestone features. All libraries are already installed.

```bash
# Backend — nothing to add
# All needed: symfony/expression-language, symfony/validator, symfony/messenger — already in composer.json

# Frontend — nothing to add
# All needed: primevue, zod, pinia — already in package.json

# Optional: if ExpressionLanguage caching becomes needed (defer)
# composer require symfony/cache  # for RedisAdapter
# (Already likely transitive dep — check with: composer show symfony/cache)
```

```bash
# Check if symfony/cache is already available
composer show symfony/cache 2>/dev/null || echo "not installed"
```

---

## Alternatives Considered

| Recommended | Alternative | Why Not |
|-------------|-------------|---------|
| `symfony/expression-language` (already installed) | Custom simple condition parser | ExpressionLanguage provides full sandbox, linting (`$el->lint($expr, [])`), operator richness (`in`, `matches`, `starts with`), and PSR-6 caching. A custom parser would need to re-implement all of this. ExpressionLanguage is what Symfony Workflow's guard expressions use internally. [HIGH confidence] |
| `Assert\Collection` (programmatic) | Separate `FormValidator` domain service with custom rules | `Assert\Collection` is built-in, tested, returns `ConstraintViolationList` with field paths. Custom service would duplicate this logic. The existing `ExecuteTaskActionHandler::validateFormData()` is already a minimal custom validator — enhance it to use `Assert\Collection` for type + regex + range validation beyond just required-check. [HIGH confidence] |
| Zod (already installed) for frontend validation | VeeValidate | VeeValidate is a form framework, not a validation library. Zod is headless — works with any UI. Given `DynamicFormField.vue` renders fields independently and `ActionFormDialog.vue` collects values in `formData: ref<Record<string, unknown>>`, Zod `safeParse()` fits perfectly without refactoring the component model. |
| Pinia store action for `executeAction` | Direct API call in component | Store centralizes loading state, error handling, and optimistic updates. Existing `task.store.ts` already has the pattern — add `executeAction(taskId, payload)` action there. Components remain thin. |
| Doctrine `json` type | Separate `form_schema` table | Separate table is over-engineering. Form schemas are part of task context, always fetched with the task, never queried independently. JSONB column on task or accessed via `WorkflowTaskLink` join is the right granularity. Decision already validated: the project uses `json` type extensively. |

---

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Formik / React Hook Form patterns | Project is Vue 3 + PrimeVue, not React. Figma prototype uses React+shadcn — adapt UI intent to PrimeVue components, not the React library choices. | PrimeVue `Dialog` + `DynamicFormField.vue` pattern (already built) |
| API Platform | Project decision: manual REST controllers for learning. Using API Platform would require significant re-architecture. | Custom Symfony controllers (already the pattern) |
| SpEL (Spring Expression Language) patterns | PHP ecosystem equivalent is Symfony ExpressionLanguage. SpEL docs/tutorials don't translate. | `symfony/expression-language` — fully documented for PHP 8.4 |
| JSON Schema (draft-07) as form_schema format | Adds complexity without benefit for this milestone. `FormFieldDefinition[]` (existing TypeScript type) is simpler and already consumed by `DynamicFormField.vue`. Zod 4.3 `z.fromJSONSchema()` exists but is premature optimization. | Keep existing `FormFieldDefinition` interface as the schema format |
| Symfony Form component | Designed for server-rendered HTML forms with CSRF. Procivo uses a JSON REST API with Vue frontend — Symfony Forms add zero value and significant complexity. | Symfony Validator (`Assert\Collection`) for backend validation, Zod for frontend |
| Global ExpressionLanguage service (singleton) | Current `ExpressionEvaluator` creates `new ExpressionLanguage()` in constructor without cache. Acceptable for low-volume dev, but becomes a performance problem in production with many gateway evaluations. | When needed: inject PSR-6 cache into `ExpressionEvaluator` constructor via Symfony DI. Use `RedisAdapter` already available in the stack. |

---

## Stack Patterns by Variant

**If a gateway condition uses process variables from user form input:**
- Variables are stored as `array<string, mixed>` in `ProcessInstance.variables` (JSONB)
- Pass directly to `ExpressionEvaluator::evaluate($condition, $variables)`
- Expression example: `approval_status == 'approved'` where `approval_status` is a form field name
- Variable keys must not collide with PHP reserved words — enforce in `FormFieldDefinition` validation

**If a task is a pool task (by_role or by_department):**
- Task created with `candidateRoleId` or `candidateDepartmentId` set, `assigneeId = null`
- Frontend shows "Claim" banner via `isPoolTask: bool` in `TaskDTO`
- `ClaimTaskCommand` / `UnclaimTaskCommand` already created (visible in git status)
- API: `POST /tasks/{id}/claim` and `POST /tasks/{id}/unclaim`

**If a task has no outgoing transitions (single linear flow):**
- `WorkflowEngine.executeAction()` handles: if no transition with matching `actionKey`, falls back to first outgoing transition when only one exists
- Frontend: show single "Complete" button, no dialog needed — use inline form rendering
- `form_schema.actions` will have exactly one entry with `key: 'complete'`

**If a transition has `from_variable` assignment for the downstream task:**
- `FormFieldCollector.injectAssigneeFieldsForDownstream()` auto-adds `employee` type picker fields
- Field name pattern: `_assignee_for_{nodeId}`
- Frontend `DynamicFormField.vue` already renders `employee` type (Select with employee options)
- This is already implemented end-to-end — no new stack needed

**If extended field validation is needed (min/max, regex, type checking):**
- Backend: enhance `ExecuteTaskActionHandler.validateFormData()` to build `Assert\Collection` programmatically
- Map `FormFieldDefinition.type` to Symfony constraints: `text` → `Assert\Type('string') + Assert\Length`, `number` → `Assert\Type('numeric') + Assert\Range`, `date` → `Assert\Date`
- Frontend: enhance Zod schema building: `text` → `z.string()`, `number` → `z.number()`, `date` → `z.coerce.date()`

---

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| `symfony/expression-language: 8.0.*` | Symfony 8.0.*, PHP 8.4 | Already in use. `ExpressionEvaluator` works as-is. Add cache + providers when needed. |
| `primevue: 4.5.4` | Vue 3.5.28, `@primevue/themes: 4.5.4` | Already installed and in use. `DynamicFormField.vue` uses `InputText`, `InputNumber`, `DatePicker`, `Select`, `Checkbox`, `Textarea`. `ActionFormDialog.vue` uses `Dialog`. |
| `zod: 4.3.6` | TypeScript 5.9.3, Vue 3.5.28 | Already installed. Zod 4.x is a breaking change from Zod 3.x (different import patterns in some cases) — check `import { z } from 'zod'` syntax which is unchanged. |
| `doctrine/orm: 3.6` + PostgreSQL 18 | `json` type maps to JSONB in PostgreSQL | Verified by existing use of `json` type in `Transition.orm.xml`, `Node.orm.xml`. No additional Doctrine extensions needed. |
| `symfony/validator: 8.0.*` | Symfony 8.0.*, PHP 8.4 | Already installed. `Assert\Collection` with programmatic field array is documented and stable. |

---

## ExpressionLanguage: Key Operators for BPM Conditions

Verified from official Symfony 8 docs — these are the operators available for gateway `condition_expression` fields:

```
# Equality
status == 'approved'
amount != 0

# Comparison (for number fields)
score > 80
days_remaining <= 3

# Collection membership
department in ['hr', 'finance', 'legal']
role not in ['intern', 'contractor']

# String matching
comment starts with 'URGENT'
name contains 'Smith'
code matches '/^[A-Z]{3}\\d{4}$/'

# Logical
approved == true and score > 50
status == 'rejected' or clarification_needed == true

# Null coalescing (for optional fields)
override_assignee ?? null

# Chaining
(amount > 1000 and department == 'finance') or is_critical == true
```

The `ExpressionEvaluator` receives `ProcessInstance.variables` as the `$variables` array. Form field values (submitted by users in `ActionFormDialog`) are merged into these variables via `ProcessInstance.mergeVariables()`.

---

## Symfony Validator: Dynamic Form Validation Pattern

Verified from official Symfony 8 docs — the pattern for dynamic form field validation:

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// Build constraints from FormFieldDefinition[] schema
private function buildCollection(array $formFields): Assert\Collection
{
    $fields = [];
    foreach ($formFields as $field) {
        $constraints = $this->constraintsForType($field['type']);
        $fieldName = $field['name'];

        if ($field['required'] ?? false) {
            $fields[$fieldName] = new Assert\Required($constraints);
        } else {
            $fields[$fieldName] = new Assert\Optional($constraints);
        }
    }

    return new Assert\Collection([
        'fields' => $fields,
        'allowExtraFields' => true,  // allow _assignee_for_* injected fields
        'allowMissingFields' => false,
    ]);
}

private function constraintsForType(string $type): array
{
    return match($type) {
        'text', 'textarea' => [new Assert\Type('string'), new Assert\NotBlank()],
        'number' => [new Assert\Type('numeric')],
        'date' => [new Assert\Date()],
        'select' => [new Assert\NotBlank()],
        'employee' => [new Assert\Uuid()],
        'checkbox' => [new Assert\Type('bool')],
        default => [],
    };
}
```

This pattern replaces the current minimal `validateFormData()` in `ExecuteTaskActionHandler`. The current implementation only checks required/non-empty — the above adds type validation.

---

## Zod: Dynamic Schema Building Pattern (Frontend)

```typescript
import { z } from 'zod'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

function buildZodSchema(fields: FormFieldDefinition[]): z.ZodObject<z.ZodRawShape> {
  const shape: z.ZodRawShape = {}

  for (const field of fields) {
    let zodType: z.ZodTypeAny

    switch (field.type) {
      case 'text':
      case 'textarea':
        zodType = field.required ? z.string().min(1) : z.string().optional()
        break
      case 'number':
        zodType = field.required ? z.number() : z.number().nullable()
        break
      case 'date':
        zodType = field.required ? z.coerce.date() : z.coerce.date().nullable()
        break
      case 'select':
        zodType = field.required ? z.string().min(1) : z.string().optional()
        break
      case 'checkbox':
        zodType = z.boolean()
        break
      case 'employee':
        zodType = field.required ? z.string().uuid() : z.string().uuid().optional()
        break
      default:
        zodType = z.unknown()
    }

    shape[field.name] = zodType
  }

  return z.object(shape)
}

// Usage in ActionFormDialog.vue
const schema = buildZodSchema([...sharedFields, ...action.formFields])
const result = schema.safeParse(formData.value)
if (!result.success) {
  // result.error.flatten().fieldErrors → { fieldName: ['error message'] }
}
```

---

## Sources

- Official Symfony 8.0 ExpressionLanguage docs — https://symfony.com/doc/current/components/expression_language.html — operators, syntax, providers verified [HIGH confidence]
- Official Symfony 8.0 ExpressionLanguage syntax — https://symfony.com/doc/current/components/expression_language/syntax.html — full operator list verified [HIGH confidence]
- Official Symfony 8.0 ExpressionLanguage extending — https://symfony.com/doc/current/components/expression_language/extending.html — ExpressionFunctionProviderInterface pattern [HIGH confidence]
- Official Symfony 8.0 Validator docs — https://symfony.com/doc/current/validation.html — programmatic validation, constraint list [HIGH confidence]
- Official Symfony 8.0 Validator `Assert\Collection` — https://symfony.com/doc/current/reference/constraints/Collection.html — dynamic collection pattern [HIGH confidence]
- Symfony 8.0 Messenger docs — https://symfony.com/doc/current/messenger.html — multi-bus CQRS configuration [HIGH confidence]
- Zod GitHub releases — https://github.com/colinhacks/zod/releases — v4.3.6 confirmed latest, `z.fromJSONSchema()` added in 4.3.0 [MEDIUM confidence — release notes only]
- Codebase direct analysis — `/Users/leleka/Projects/procivo/backend/` and `/Users/leleka/Projects/procivo/frontend/` — existing implementations verified [HIGH confidence]

---
*Stack research for: Procivo BPM — Workflow-Task Integration Milestone*
*Researched: 2026-02-28*
