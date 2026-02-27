# Testing Patterns

**Analysis Date:** 2026-02-27

## Test Framework

**Backend (PHP):**
- **Runner:** PHPUnit v13.0
  - Config: `/Users/leleka/Projects/procivo/backend/phpunit.dist.xml`
  - Bootstrap: `/Users/leleka/Projects/procivo/backend/tests/bootstrap.php`
  - Cache: `.phpunit.cache/`

**Frontend (TypeScript/Vue):**
- **Runner:** Vitest v4.0.18
  - Config: `/Users/leleka/Projects/procivo/frontend/vitest.config.ts`
  - Test environment: jsdom (browser simulation)

## Test File Organization

**Backend:**

**Location:** Co-located with source code in `tests/` directory mirror
```
tests/
├── Unit/
│   ├── Shared/
│   │   └── Domain/
│   │       ├── ValueObject/
│   │       │   ├── EmailTest.php
│   │       │   ├── UuidTest.php
│   │       │   └── CreatedAtTest.php
│   │       └── AggregateRootTest.php
│   ├── Identity/
│   │   ├── Domain/
│   │   │   ├── Entity/
│   │   │   │   └── UserTest.php
│   │   │   └── ValueObject/
│   │   │       ├── UserIdTest.php
│   │   │       ├── UserStatusTest.php
│   │   │       └── HashedPasswordTest.php
│   │   └── Application/
│   │       └── Command/
│   │           ├── RegisterUserHandlerTest.php
│   │           └── ChangePasswordHandlerTest.php
│   └── Organization/
│       ├── Domain/
│       │   ├── Entity/
│       │   │   ├── OrganizationTest.php
│       │   │   ├── DepartmentTest.php
│       │   │   ├── EmployeeTest.php
│       │   │   └── PositionTest.php
│       │   └── ValueObject/
│       │       ├── OrganizationSlugTest.php
│       │       ├── DepartmentPathTest.php
│       │       └── DepartmentCodeTest.php
│       └── Application/
│           └── Command/
│               ├── CreateOrganizationHandlerTest.php
│               ├── CreateDepartmentHandlerTest.php
│               └── HireEmployeeHandlerTest.php
└── bootstrap.php
```

**Naming:** `{ClassUnderTest}Test.php`
- Example: `UserTest.php` for `User.php`, `CreateTaskHandlerTest.php` for `CreateTaskHandler.php`

**Frontend:**

**Location:** Co-located next to source files (not yet established, tests minimal)
- Suggested: `src/**/*.spec.ts` or `src/__tests__/**/*.spec.ts`
- Pattern matches ESLint config: `files: ['src/**/__tests__/*']`
- Currently no test files found in frontend; follows pattern when added

**Naming:** `{component}.spec.ts` or `{function}.spec.ts`

## Test Structure

**Backend PHPUnit:**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Event\UserActivatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[Test]
    public function itRegistersANewUser(): void
    {
        // Arrange
        $user = $this->createUser();

        // Act & Assert
        self::assertNotEmpty($user->id()->value());
        self::assertSame('john@example.com', $user->email()->value());
    }

    #[Test]
    public function itRecordsUserRegisteredEvent(): void
    {
        $user = $this->createUser();

        $events = $user->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegisteredEvent::class, $events[0]);
    }

    #[Test]
    public function itThrowsWhenEmailAlreadyExists(): void
    {
        // Arrange
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('existsByEmail')->willReturn(true);

        // Act & Assert
        $this->expectException(UserAlreadyExistsException::class);
        // trigger action
    }

    // Private helper
    private function createUser(): User
    {
        return User::register(
            id: UserId::generate(),
            email: new Email('john@example.com'),
            password: new HashedPassword('hashed-password'),
            firstName: 'John',
            lastName: 'Doe',
        );
    }
}
```

**Patterns:**

1. **Test Method Naming:** `it[Does][Something]()` (camelCase with `it` prefix)
   - Example: `itRegistersANewUser()`, `itActivatesUser()`, `itThrowsWhenSlugExists()`
   - Reads as documentation: "it registers a new user"

2. **Attributes:** Use `#[Test]` instead of `public function test*()`
   - Cleaner than prefix-based naming
   - Part of PHPUnit v13 standard

3. **Class Declaration:**
   ```php
   final class UserTest extends TestCase
   ```
   - `final` prevents inheritance (tests should be simple)
   - Extends `PHPUnit\Framework\TestCase`

4. **Assertions:** Use `self::assert*()` static calls
   - `self::assertSame()` — strict equality (===)
   - `self::assertNull()` — null check
   - `self::assertTrue()` / `self::assertFalse()` — boolean
   - `self::assertCount()` — array count
   - `self::assertInstanceOf()` — type checking
   - `self::assertContains()` — array membership

5. **Exception Testing:**
   ```php
   $this->expectException(UserAlreadyExistsException::class);
   // Code that throws
   ```

6. **Helpers:** Private methods for test data setup
   ```php
   private function createUser(): User { }
   ```

## Mocking

**Framework:** PHPUnit's built-in mocking (no external library)

**Patterns:**

```php
// Create mock of interface
$repository = $this->createMock(UserRepositoryInterface::class);

// Set return values
$repository->method('existsByEmail')->willReturn(false);

// Assert method was called exactly once with params
$repository->expects(self::once())->method('save');

// Use mock in handler
$handler = new RegisterUserHandler($repository, $hasher);
$handler(new RegisterUserCommand(...));
```

**Example from codebase:**
```php
#[Test]
public function itRegistersANewUser(): void
{
    $repository = $this->createMock(UserRepositoryInterface::class);
    $hasher = $this->createMock(PasswordHasherInterface::class);

    $repository->method('existsByEmail')->willReturn(false);
    $repository->expects(self::once())->method('save');
    $hasher->method('hash')->with('plain-password')->willReturn('hashed-password');

    $handler = new RegisterUserHandler($repository, $hasher);

    $userId = $handler(new RegisterUserCommand(
        email: 'john@example.com',
        password: 'plain-password',
        firstName: 'John',
        lastName: 'Doe',
    ));

    self::assertNotEmpty($userId);
}
```

**What to Mock:**
- Repository interfaces (external data source)
- External services (PasswordHasherInterface, MailerInterface, etc.)
- Ports/Adapters (infrastructure layer)

**What NOT to Mock:**
- Domain entities (test them directly)
- Value objects (test real behavior)
- Domain services (test real logic)

## Fixtures and Factories

**Test Data:**

Currently using **helper methods** instead of dedicated factories:

```php
private function createUser(): User
{
    return User::register(
        id: UserId::generate(),
        email: new Email('john@example.com'),
        password: new HashedPassword('hashed-password-value'),
        firstName: 'John',
        lastName: 'Doe',
    );
}
```

**Location:** Private methods within test class

**Considerations:**
- Keep fixtures simple and minimal
- Use builder pattern if object construction becomes complex
- Factories can be extracted to `Tests/Fixture/` if shared across multiple test classes

**Data Providers:**

Used for parameterized testing:

```php
#[Test]
#[DataProvider('invalidEmails')]
public function itThrowsOnInvalidEmail(string $invalid): void
{
    $this->expectException(InvalidArgumentException::class);
    new Email($invalid);
}

/**
 * @return iterable<string, array{string}>
 */
public static function invalidEmails(): iterable
{
    yield 'empty string' => [''];
    yield 'no @' => ['userexample.com'];
    yield 'no domain' => ['user@'];
    yield 'no user' => ['@example.com'];
    yield 'spaces in middle' => ['user @example.com'];
}
```

## Coverage

**Requirements:** None explicitly enforced

**PHPUnit Config:**
- No coverage driver configured in `phpunit.dist.xml`
- Coverage can be run manually: `php bin/phpunit --coverage-html coverage/`

**Current State:**
- ~50+ tests across Shared, Identity, Organization modules
- Test coverage not yet measured or enforced
- Recommendation: Add `<coverage>` section to phpunit.xml when needed

## Test Types

**Unit Tests:**
- **Scope:** Single class in isolation
- **Approach:** Test public interface, mock dependencies
- **Location:** `tests/Unit/{Module}/{Layer}/`

Examples:
- `EmailTest.php` — Value object validation
- `UserTest.php` — Entity state and events
- `RegisterUserHandlerTest.php` — Handler with mocked repository

**Integration Tests:**
- **Status:** Not yet implemented
- **Would cover:** Handler + Repository + Doctrine, end-to-end flows
- **Location:** `tests/Integration/{Module}/`

**E2E Tests:**
- **Status:** Not implemented
- **Framework:** Not decided (would be Symfony browser-kit or similar)
- **Approach:** Full HTTP requests, database fixtures

## Common Patterns

**Async Testing:**

Not used in backend (PHP is synchronous). Frontend Vitest supports async:

```typescript
// Frontend example (not yet in codebase)
async function fetchUsers() { /* ... */ }

// Would be tested as:
it('fetches users', async () => {
  const users = await fetchUsers()
  expect(users).toHaveLength(1)
})
```

**Error Testing:**

```php
#[Test]
public function itThrowsWhenSlugAlreadyExists(): void
{
    $repository = $this->createMock(OrganizationRepositoryInterface::class);
    $repository->method('existsBySlug')->willReturn(true);

    $handler = new CreateOrganizationHandler($repository, $userProvider);

    $this->expectException(OrganizationSlugAlreadyExistsException::class);

    $handler(new CreateOrganizationCommand(
        id: OrganizationId::generate()->value(),
        name: 'Acme Corp',
        slug: 'acme-corp',
    ));
}
```

**State & Events Testing:**

Test domain event recording:

```php
#[Test]
public function itActivatesUser(): void
{
    $user = $this->createUser();
    $user->pullDomainEvents();  // Clear initial registration event

    $user->activate();

    self::assertSame(UserStatus::Active, $user->status());

    $events = $user->pullDomainEvents();
    self::assertCount(1, $events);
    self::assertInstanceOf(UserActivatedEvent::class, $events[0]);
}
```

## Run Commands

**Backend PHPUnit:**
```bash
# Run all tests
php bin/phpunit

# Run specific test class
php bin/phpunit tests/Unit/Identity/Domain/Entity/UserTest.php

# Run with coverage report
php bin/phpunit --coverage-html coverage/

# Run in watch mode (requires phpunit-watch)
php bin/phpunit --watch
```

**Frontend Vitest:**
```bash
# Run all tests
npm run test:unit

# Watch mode
npm run test:unit -- --watch

# Coverage
npm run test:unit -- --coverage
```

**Linting & Static Analysis:**

```bash
# Backend PHPStan
php bin/phpstan

# Backend PHP CS Fixer
php bin/php-cs-fixer fix

# Frontend ESLint & Oxlint
npm run lint
```

## PHPUnit Configuration Details

**File:** `/Users/leleka/Projects/procivo/backend/phpunit.dist.xml`

```xml
<phpunit colors="true"
         failOnDeprecation="true"
         failOnNotice="true"
         failOnWarning="true"
         bootstrap="tests/bootstrap.php">
    <php>
        <server name="APP_ENV" value="test" force="true" />
        <server name="SHELL_VERBOSITY" value="-1" />
    </php>
    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

**Key Settings:**
- `failOnDeprecation="true"` — Fail if deprecated functions used
- `failOnNotice="true"` — Fail on PHP notices
- `failOnWarning="true"` — Fail on PHP warnings
- `bootstrap="tests/bootstrap.php"` — Setup file for all tests
- App environment forced to `test` mode
- Covers all files in `src/` directory

## Vitest Configuration Details

**File:** `/Users/leleka/Projects/procivo/frontend/vitest.config.ts`

```typescript
export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      exclude: [...configDefaults.exclude, 'e2e/**'],
      root: fileURLToPath(new URL('./', import.meta.url)),
    },
  }),
)
```

**Key Settings:**
- `environment: 'jsdom'` — Simulate browser DOM
- Excludes node_modules + e2e tests
- Integrates with Vite build config

---

*Testing analysis: 2026-02-27*
