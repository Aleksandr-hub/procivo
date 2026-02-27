# Coding Conventions

**Analysis Date:** 2026-02-27

## Naming Patterns

**Files:**
- **Classes:** PascalCase for all class files
  - Example: `UserTest.php`, `CreateTaskHandler.php`, `OrganizationSlug.php`
  - PHP files in `src/` folders follow domain structure: `Domain/`, `Application/`, `Infrastructure/`, `Presentation/`
- **Functions/Methods:** camelCase
  - Test method names follow camelCase pattern: `itRegistersANewUser()`, `itThrowsWhenEmailAlreadyExists()`
  - Handler methods use `__invoke()` as the entry point
  - Property getters: `value()`, `id()`, `email()`, `password()`
  - Query/boolean methods: `exists()`, `isActive()`, `isPending()`, `equals()`
- **Variables:** camelCase in all languages
  - PHP: `$userId`, `$organizationId`, `$hashedPassword`
  - TypeScript/Vue: `const userId`, `let currentTask`, `ref<TaskDTO>`
- **Constants:** CONSTANT_CASE for configuration values
- **Vue Components:** PascalCase in files (e.g., `DynamicFormField.vue`), kebab-case when referenced in templates

**Backend PHP:**
- Classes: `readonly class User`, `final class UserTest`
- Domain Events: `UserRegisteredEvent`, `UserActivatedEvent` (past tense)
- Commands: `CreateTaskCommand`, `RegisterUserCommand`
- Handlers: `CreateTaskHandler`, `RegisterUserHandler`
- Value Objects: `Email`, `UserId`, `OrganizationSlug`
- Exceptions: `UserAlreadyExistsException`, `InvalidArgumentException`, `OrganizationNotFoundException`

**Frontend TypeScript/Vue:**
- Store functions: `useTaskStore`, `useEmployeeStore`, `useLocale`, `useTheme`
- Composables: `useResponsive`, `useI18n`
- Utils: `getApiErrorMessage`, `formatDate`, `getStatusSeverity`
- API methods: `taskApi.list()`, `taskApi.create()`, `processDefinitionApi.getGraph()`
- Type suffixes: `.types.ts` for type definitions, `.api.ts` for API clients

## Code Style

**Formatting:**
- **PHP:** PHP CS Fixer v3.94 with Symfony ruleset
- **Frontend:** Prettier 3.8.1 + Oxlint v1.47

**Backend PHP Rules:**
```php
// Declare strict types
declare(strict_types=1);

// Symfony style: camelCase test methods
#[Test]
public function itRegistersANewUser(): void { }

// Always use readonly where possible
readonly class Email { }

// Type declarations required
private string $value;
public function value(): string { }

// Import organization: alphabetical
use App\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\Email;
```

**PHP CS Fixer Configuration (`/Users/leleka/Projects/procivo/backend/.php-cs-fixer.dist.php`):**
```php
'@Symfony' => true,
'@Symfony:risky' => true,
'declare_strict_types' => true,
'strict_param' => true,
'array_syntax' => ['syntax' => 'short'],  // Use [] not array()
'ordered_imports' => ['sort_algorithm' => 'alpha'],
'no_unused_imports' => true,
'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'match', 'parameters']],
```

**Frontend Linting:**
- **ESLint:** `eslint.config.ts` with Vue 3 + TypeScript support, Oxlint plugin
- **Prettier:** `.prettierrc.json` configured with:
  - `semi: false` — no semicolons
  - `singleQuote: true` — single quotes
  - `printWidth: 100` — line width limit
- **Vue Linting:** `@vue/eslint-config-typescript`, `eslint-plugin-vue`

**Frontend Code Style:**
```typescript
// Use const/let, typed props/emits
const emit = defineEmits<{ 'update:modelValue': [value: unknown] }>()

// Template imports use kebab-case
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

// Single quotes, no semicolons
const empStore = useEmployeeStore()
```

## Linting

**Backend:**
- **PHPStan:** Level 6 (highest)
  - Config: `/Users/leleka/Projects/procivo/backend/phpstan.neon`
  - Enforces strict typing, null safety checks
  - Excludes: `src/Kernel.php`

**Frontend:**
- **Oxlint:** Fast Rust linter for JavaScript/TypeScript
- **ESLint:** Vue + TypeScript config with Vitest plugin for test files
- Both run as `npm run lint:oxlint && npm run lint:eslint`

## Import Organization

**PHP Backend:**

1. **PHP built-ins** (none, only use as needed)
2. **Doctrine/Symfony classes**
3. **Internal domain classes** (App\*)
4. **Alphabetical order** within each group

Example:
```php
use App\TaskManager\Domain\Repository\TaskRepositoryInterface;
use App\Workflow\Domain\Service\ExpressionEvaluator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
```

**Frontend TypeScript:**

1. **Vue/Pinia imports** (core framework)
2. **Third-party libraries** (axios, zod, etc.)
3. **Type imports** (`type` keyword)
4. **Internal modules** (relative `@/` paths)

Example:
```typescript
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
```

**Path Aliases:**
- Frontend uses `@/` to refer to `src/` (configured in `vite.config.ts` and `tsconfig.app.json`)
- Avoid relative paths in deep module trees; use `@/` instead

## Error Handling

**Backend Domain Exceptions:**
- All exceptions inherit from `DomainException` (extends `\DomainException`)
  - File: `/Users/leleka/Projects/procivo/backend/src/Shared/Domain/Exception/DomainException.php`
  - Has `statusCode` property (default 400, override for 404, 409, etc.)
  - Implement `TranslatableExceptionInterface` for i18n support

Example:
```php
final class UserAlreadyExistsException extends DomainException implements TranslatableExceptionInterface
{
    private string $translationKey = 'error.user_already_exists';

    public function getTranslationKey(): string { return $this->translationKey; }
    public function getTranslationParams(): array { return []; }
}
```

**Value Object Validation:**
- Throw `InvalidArgumentException` in constructors for validation
- Use regex/filters for format validation
- Example:
  ```php
  public function __construct(string $value)
  {
      if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          throw new InvalidArgumentException(\sprintf('Invalid email: "%s".', $value));
      }
  }
  ```

**Frontend Error Handling:**
- Use error boundary pattern in async functions
- Extract error messages with `getApiErrorMessage()` utility
  - File: `/Users/leleka/Projects/procivo/frontend/src/shared/utils/api-error.ts`
  - Handles Axios error response structure
- Pinia stores use try-finally for loading state management
  ```typescript
  async function fetchTasks(orgId: string) {
    loading.value = true
    try {
      tasks.value = await taskApi.list(orgId)
    } finally {
      loading.value = false
    }
  }
  ```

## Logging

**Backend:**
- Monolog via Symfony (configured in `config/packages/monolog.yaml`)
- No explicit logging patterns enforced; use standard Symfony logger when needed
- Log level decisions left to handler/service implementation

**Frontend:**
- Console.log acceptable for development/debugging
- No centralized logging library currently in use
- Error tracking would be added later (Sentry integration possible)

## Comments

**When to Comment:**

1. **Complex algorithms:** Explain the "why" not the "what"
2. **Domain logic:** Business rules that aren't obvious from code
3. **Workarounds:** Document temporary fixes with issue links
4. **Type hints:** Use inline comments for complex array/object structures

Example:
```php
/** @var list<string> */
private array $roles;

/** @var array<string, string> */
private array $translationParams = [];

// Doctrine-mapped field (required for ORM compatibility)
private string $id;
```

**Do NOT Comment:**
- Self-explanatory code (method names should be clear)
- What getters/setters do (they're obvious)

**PHPDoc/JSDoc:**
- Used for public API (class, public methods)
- Backend: Method-level docblocks for complex parameters/returns
- Frontend: TypeScript types replace JSDoc (prefer type hints)

Example:
```php
/**
 * @return list<DomainEvent>
 */
public function pullDomainEvents(): array { }

/**
 * @return iterable<string, array{string}>
 */
public static function invalidEmails(): iterable { }
```

## Function Design

**Size:**
- Prefer small functions (<20 lines)
- Handlers use `__invoke()` as single entry point
- Extract complex logic into service classes

**Parameters:**
- Use named parameters in PHP (especially in constructors)
- Handlers use constructor dependency injection + `__invoke(Command $command)`

Example:
```php
#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private AssignmentResolver $assignmentResolver,
    ) {}

    public function __invoke(CreateTaskCommand $command): void
    {
        // Single responsibility: coordinate domain logic
    }
}
```

**Return Values:**
- Domain operations return `void` when persisting
- Handlers return `void` or domain value ID (string)
- Queries return DTOs (Data Transfer Objects)
- Use strict types (`?Type` for nullable)

Example:
```php
public function __invoke(RegisterUserCommand $command): string
{
    // Returns user ID
    return $userId;
}

public function id(): UserId { }  // Return as VO
public function value(): string { }  // Return primitive
```

## Module Design

**Module Structure:**
- Modules follow Clean Architecture: `Domain/`, `Application/`, `Infrastructure/`, `Presentation/`
- Each module in `src/{ModuleName}/`
- Example: `/Users/leleka/Projects/procivo/backend/src/TaskManager/`

**Exports:**
- Export via interface contracts (repository, port, handler)
- Never export internal domain logic directly
- Use `AsMessageHandler` attribute for command/query registration

**Barrel Files:**
- Not used; import directly from source files
- Easier to follow dependency chains

**Repositories:**
- Always interface-based: `TaskRepositoryInterface`
- Located in `Domain/Repository/` (interface)
- Implemented in `Infrastructure/Repository/` (Doctrine, etc.)

---

*Convention analysis: 2026-02-27*
