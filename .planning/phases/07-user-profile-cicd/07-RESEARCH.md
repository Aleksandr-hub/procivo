# Phase 7: User Profile + CI/CD - Research

**Researched:** 2026-03-01
**Domain:** User profile management (avatar upload to S3, profile editing, password change) + CI/CD tooling (GitHub Actions, lefthook pre-commit hooks, documentation)
**Confidence:** HIGH

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PROF-01 | User can view and edit profile (firstName, lastName, email) on a dedicated profile page | Identity domain already has User entity with firstName/lastName/email fields; PUT endpoint + UpdateProfile command needed |
| PROF-02 | User can upload avatar image to S3 with server-side validation (type via finfo_buffer, 5MB max size) | S3FileStorage pattern already exists in TaskManager; fileinfo PHP extension confirmed loaded in Docker; avatar_path column needed on identity_users |
| PROF-03 | User avatar displayed in topbar, comments, employee lists, and task assignments | PrimeVue Avatar component already in use in TaskDetailSidebar (label-based); needs `image` prop wired to presigned URL; no new library needed |
| PROF-04 | GET /api/v1/auth/me returns full profile including avatar URL (24h presigned S3 link) | GetCurrentUserHandler returns UserDTO; presigned URL TTL must be extended from 1h (current S3FileStorage.getUrl) to 24h; UserDTO must gain avatarUrl nullable field |
| PROF-05 | User can change password from profile page (current + new password form, uses existing PUT /api/v1/auth/password) | ChangePasswordCommand + handler already exist and are wired; frontend only needs a new ProfilePage with a password form wired to the existing API endpoint |
| ADMN-03 | GitHub Actions CI pipeline: CS Fixer + PHPStan + PHPUnit + frontend type-check + ESLint on every push | No .github/ directory exists yet; all tools already configured locally (phpstan.neon, .php-cs-fixer.dist.php, phpunit.dist.xml, eslint.config.ts, package.json scripts) |
| ADMN-04 | Pre-commit hooks via lefthook (lint + type-check on staged files) | No lefthook.yml exists; lefthook is the specified tool (from requirements); needs installation + config targeting staged .php and .ts/.vue files |
| ADMN-05 | .env.example + README with setup instructions | No README.md and no .env.example exist anywhere in the project; both need to be created from scratch |
</phase_requirements>

---

## Summary

Phase 7 has two independent workstreams: user profile features and CI/CD infrastructure. The profile workstream extends the existing `Identity` bounded context with minimal new surface area — the `User` aggregate already has all needed fields (`firstName`, `lastName`, `email`, `password`), the password-change command and handler already exist, and `GET /api/v1/auth/me` already works. The primary new work is: (1) adding an `avatarPath` nullable column to `identity_users`, (2) creating `UpdateProfile` command + handler, (3) creating an `UploadAvatar` command + handler that reuses the S3 pattern already established in `TaskManager`, and (4) creating a new `ProfilePage.vue` on the frontend.

The CI/CD workstream is pure tooling — all linting and test tools already exist and are configured locally. The work is writing three config files: `.github/workflows/ci.yml`, `lefthook.yml` at project root, and `README.md` + `backend/.env.example`. No new packages are needed on the PHP side. Lefthook requires one npm/global install and a minimal YAML config.

The avatar display integration is the most cross-cutting piece: the PrimeVue `Avatar` component is already in use in `TaskDetailSidebar` using initials (`:label="initials"`). The change is to add an `:image="user.avatarUrl || undefined"` prop so it renders the presigned URL when available and falls back to initials gracefully.

**Primary recommendation:** Split into two plans — Plan 01: Backend profile API (UpdateProfile + UploadAvatar commands, UserDTO extension, migration) + Plan 02: Frontend ProfilePage + avatar display integration + CI/CD files (GitHub Actions, lefthook, README, .env.example).

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| league/flysystem-aws-s3-v3 | ^3.32 | S3 file storage | Already in composer.json, used by TaskManager |
| aws/aws-sdk-php (transitive) | via flysystem | S3Client for presigned URLs | Already used in S3FileStorage.php |
| friendsofphp/php-cs-fixer | ^3.94 | PHP code style | Already in require-dev |
| phpstan/phpstan | ^2.1 | Static analysis | Already in require-dev, configured at level 6 |
| phpunit/phpunit | ^13.0 | PHP unit tests | Already in require-dev |
| lefthook | latest | Git pre-commit hooks | Specified in ADMN-04 requirement |
| eslint | ^9.39.2 | Frontend linting | Already in devDependencies |
| vue-tsc | ^3.2.4 | Frontend type-check | Already in devDependencies |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHP fileinfo extension | built-in | finfo_buffer() for MIME validation | Already installed in Docker image (confirmed) |
| PrimeVue Avatar | ^4.5.4 | Avatar display | Already in use — just needs `image` prop |
| GitHub Actions ubuntu-latest | N/A | CI runner | Standard free runner, sufficient for this stack |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| lefthook | husky + lint-staged | husky is Node-only, lefthook is language-agnostic and fits a PHP+TS monorepo better |
| finfo_buffer() | `$file->getMimeType()` (Symfony) | Symfony uses filesystem MIME detection which trusts the file extension — `finfo_buffer()` reads magic bytes from file content, which is what PROF-02 requires |
| Shared FileStoragePort in Identity | Copy S3FileStorage to Identity | Copying violates DRY; but cross-module port sharing needs careful design — better to create `Identity\Application\Port\AvatarStorageInterface` that delegates to the same S3 infrastructure |

**Installation (lefthook):**
```bash
# Install in project root (package.json optional — can install globally or via npx)
npm install --save-dev lefthook
# OR install globally: brew install lefthook
```

---

## Architecture Patterns

### Recommended Project Structure (new files only)

```
backend/src/Identity/
├── Application/
│   ├── Command/
│   │   ├── UpdateProfile/
│   │   │   ├── UpdateProfileCommand.php
│   │   │   └── UpdateProfileHandler.php
│   │   └── UploadAvatar/
│   │       ├── UploadAvatarCommand.php
│   │       └── UploadAvatarHandler.php
│   ├── Port/
│   │   └── AvatarStorageInterface.php   # mirrors FileStorageInterface
│   └── DTO/
│       └── UserDTO.php                  # add avatarUrl: ?string
│
├── Domain/
│   └── Entity/
│       └── User.php                     # add avatarPath field + updateProfile(), setAvatarPath()
│
├── Infrastructure/
│   ├── Repository/                      # no changes
│   └── Storage/
│       └── S3AvatarStorage.php          # reuses S3Client pattern from TaskManager
│
└── Presentation/
    └── Controller/
        └── AuthController.php           # add PUT /me + POST /me/avatar endpoints

backend/migrations/
└── Version20260301120000.php            # ADD COLUMN avatar_path VARCHAR NULL to identity_users

.github/
└── workflows/
    └── ci.yml

lefthook.yml                             # project root
README.md                               # project root
backend/.env.example
frontend/.env.example                   # if needed (currently no frontend .env)

frontend/src/modules/auth/
├── pages/
│   └── ProfilePage.vue                 # new dedicated page
└── api/
    └── user.api.ts                     # extend with updateProfile, uploadAvatar, changePassword
```

### Pattern 1: UpdateProfile Command (existing Identity pattern)

**What:** New command + handler pair following the established CQRS pattern in Identity.
**When to use:** Mutating User aggregate state.

```php
// backend/src/Identity/Application/Command/UpdateProfile/UpdateProfileCommand.php
final readonly class UpdateProfileCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {}
}

// Handler — follows ChangePasswordHandler pattern exactly
#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateProfileHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(UpdateProfileCommand $command): void
    {
        $user = $this->userRepository->findById(UserId::fromString($command->userId));
        if (null === $user) {
            throw new DomainException(sprintf('User "%s" not found.', $command->userId));
        }
        $user->updateProfile($command->firstName, $command->lastName, new Email($command->email));
        $this->userRepository->save($user);
    }
}
```

### Pattern 2: Avatar Upload — finfo_buffer() Validation

**What:** Use PHP's `finfo_buffer()` to read magic bytes from the raw file content, not trusting the client-supplied MIME type.
**When to use:** Any server-side file type validation where security matters.

```php
// In UploadAvatarHandler or a dedicated validator
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new \finfo(\FILEINFO_MIME_TYPE);
$detectedMime = $finfo->buffer($command->fileContent); // reads magic bytes

if (!in_array($detectedMime, $allowed, true)) {
    throw new \InvalidArgumentException('Invalid image type.');
}

if (strlen($command->fileContent) > 5 * 1024 * 1024) {
    throw new \InvalidArgumentException('Avatar exceeds 5MB limit.');
}
```

### Pattern 3: Presigned URL with 24h TTL

**What:** `S3FileStorage::getUrl()` currently uses `'+1 hour'`. For avatar URLs returned by `GET /me`, use `'+24 hours'`.
**When to use:** AvatarStorageInterface should have a separate `getAvatarUrl()` that uses the 24h TTL, or accept TTL as a parameter.

```php
// S3AvatarStorage::getUrl() — identical to S3FileStorage but TTL is 24 hours
$presignedRequest = $this->publicClient->createPresignedRequest($command, '+24 hours');
```

### Pattern 4: UserDTO Extension

**What:** Add nullable `avatarUrl` field. Handler fetches presigned URL from storage only when `avatarPath` is not null.

```php
final readonly class UserDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $status,
        public array $roles,
        public string $createdAt,
        public ?string $avatarUrl,  // NEW — null when no avatar set
    ) {}

    public static function fromEntity(User $user, ?string $avatarUrl): self
    {
        return new self(
            // ...existing fields...
            avatarUrl: $avatarUrl,
        );
    }
}
```

### Pattern 5: GitHub Actions CI

**What:** Single workflow file triggered on push and PR to any branch.

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
  pull_request:

jobs:
  backend:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:18-alpine
        env:
          POSTGRES_DB: procivo_test
          POSTGRES_USER: procivo
          POSTGRES_PASSWORD: procivo
        ports: ["5432:5432"]
        options: --health-cmd pg_isready --health-interval 5s --health-timeout 5s --health-retries 5
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql, intl, zip, gd, bcmath, sockets, amqp, redis, fileinfo
      - name: Install dependencies
        working-directory: backend
        run: composer install --no-interaction --prefer-dist
      - name: CS Fixer
        working-directory: backend
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
      - name: PHPStan
        working-directory: backend
        run: vendor/bin/phpstan analyse
      - name: PHPUnit
        working-directory: backend
        env:
          APP_ENV: test
          DATABASE_URL: postgresql://procivo:procivo@localhost:5432/procivo_test?serverVersion=18&charset=utf8
        run: vendor/bin/phpunit

  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
      - name: Install dependencies
        working-directory: frontend
        run: npm ci
      - name: Type check
        working-directory: frontend
        run: npm run type-check
      - name: ESLint
        working-directory: frontend
        run: npm run lint
```

### Pattern 6: Lefthook Pre-commit Hooks

**What:** YAML config at project root. Runs CS Fixer on staged `.php` files and ESLint on staged `.ts`/`.vue` files. Both are run in fix mode so the commit is fixed before failing.

```yaml
# lefthook.yml (project root)
pre-commit:
  parallel: false
  commands:
    php-cs-fixer:
      glob: "backend/**/*.php"
      run: docker compose exec -T php vendor/bin/php-cs-fixer fix {staged_files} --config=backend/.php-cs-fixer.dist.php
      stage_fixed: true

    eslint:
      glob: "frontend/**/*.{ts,vue}"
      run: cd frontend && npx eslint --fix {staged_files}
      stage_fixed: true
```

**Note:** `stage_fixed: true` re-stages auto-fixed files so the commit includes the fixes. This is the canonical lefthook pattern for fixers.

### Pattern 7: PrimeVue Avatar with Image + Fallback

**What:** PrimeVue `Avatar` component accepts either `:label` (initials) or `:image` (URL). When `image` is set, it shows the image; when null/undefined it falls back to label.

```vue
<!-- In AppTopbar.vue, TaskDetailSidebar.vue, TaskComments.vue -->
<Avatar
  :image="user.avatarUrl ?? undefined"
  :label="user.avatarUrl ? undefined : initials"
  shape="circle"
  size="small"
/>
```

This pattern means: if `avatarUrl` exists, show the image; otherwise show the initials label. No additional library needed.

### Anti-Patterns to Avoid

- **Trusting client MIME type:** `UploadedFile::getClientMimeType()` in Symfony reads the client-supplied Content-Type header — not secure. Always use `finfo_buffer()` on the actual file content.
- **Sharing S3FileStorage directly across modules:** TaskManager's `S3FileStorage` implements `TaskManager\Application\Port\FileStorageInterface`. Identity should define its own `AvatarStorageInterface` port and its own infrastructure implementation (`S3AvatarStorage`) wired in `services.yaml`. Cross-domain direct dependencies violate bounded context isolation.
- **Storing presigned URLs in the database:** Presigned URLs expire. Store only the `avatar_path` (S3 key) in the DB and generate the URL on-demand in the query handler.
- **Running lefthook on all files:** Always use `glob` to filter to staged files only. Running CS Fixer on the entire `src/` on every commit is too slow.
- **Setting `+1 hour` for avatar presigned URL in `/me` response:** `/me` is cached by the frontend store and used for 24h sessions. Use `+24 hours` to avoid broken image links mid-session.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| MIME type detection from binary | Custom magic byte reader | `finfo_buffer()` (PHP ext-fileinfo, already loaded) | Magic bytes are complex; fileinfo handles all formats correctly |
| S3 presigned URL generation | Custom HMAC signing | `S3Client::createPresignedRequest()` (aws-sdk-php) | AWS signature v4 is complex and rotates |
| Pre-commit hook runner | Custom shell scripts | lefthook | Shell scripts are fragile, not cross-platform; lefthook handles staged files, parallel runs, and re-staging |
| CI postgres service | Manually start docker in CI | GitHub Actions `services:` key | Services start automatically, health-checked before job steps |
| Avatar initials fallback | Custom JS | PrimeVue Avatar `:label` prop | Already implemented and used throughout the codebase |

**Key insight:** The entire avatar pipeline (upload → validate → S3 store → presigned URL → display) is already pattern-matched by `TaskAttachment` in `TaskManager`. The only difference is validation rules (5MB, image MIME only) and the presigned URL TTL (24h vs 1h).

---

## Common Pitfalls

### Pitfall 1: finfo_buffer() vs getClientMimeType()
**What goes wrong:** Developer uses `$file->getClientMimeType()` (Symfony UploadedFile), which reads the Content-Type header sent by the browser — attacker can forge this to bypass image-only restriction.
**Why it happens:** Symfony's UploadedFile API is convenient and appears to do MIME detection.
**How to avoid:** Always call `finfo_buffer()` on `file_get_contents($file->getPathname())`. The PROF-02 requirement explicitly names `finfo_buffer()`.
**Warning signs:** Code that reads MIME from `$file->getMimeType()` or `$file->getClientMimeType()` without a `finfo` call.

### Pitfall 2: PHPUnit in CI Failing Due to Missing Services
**What goes wrong:** PHPUnit tests fail in CI because they try to connect to a real PostgreSQL instance.
**Why it happens:** Tests in `APP_ENV=test` that extend `KernelTestCase` or use Doctrine need a live DB. The project currently only has unit tests (`tests/Unit/`), so this is low risk — but must stay that way or a test DB service must be added.
**How to avoid:** Verify all tests in `tests/Unit/` are pure unit tests (use mocks, no Doctrine). If integration tests are added later, add a separate `services: postgres:` in the CI job.
**Warning signs:** `PHPUnit` CI job fails with "Connection refused to 127.0.0.1:5432" and there are no `services:` in the workflow.

### Pitfall 3: lefthook Runs Docker exec but Docker Not Available in CI
**What goes wrong:** `lefthook.yml` references `docker compose exec php vendor/bin/...` — this works locally but Docker is not available in the CI runner.
**Why it happens:** lefthook runs pre-commit hooks in the developer's environment (where Docker is running), not in CI. CI has its own separate job for linting.
**How to avoid:** This is intentional and correct — lefthook is for local pre-commit only. CI runs the same tools natively. The `ci.yml` job installs PHP and runs `vendor/bin/php-cs-fixer` directly, not via Docker.

### Pitfall 4: Avatar URL Breaks After 1 Hour
**What goes wrong:** User uploads avatar, sees it in the topbar, but after the presigned URL's 1-hour TTL expires the image shows as broken.
**Why it happens:** The existing `S3FileStorage::getUrl()` uses `'+1 hour'`. If you reuse that method for the avatar URL in UserDTO, sessions longer than 1 hour will see broken images.
**How to avoid:** Use `'+24 hours'` in `S3AvatarStorage::getUrl()`. The frontend should also call `fetchUser()` on page refresh (already done via `auth.store.initialize()`) to get a fresh URL.

### Pitfall 5: Email Change Without Uniqueness Check
**What goes wrong:** User changes email to one already taken by another user — database unique constraint violation returns a 500.
**Why it happens:** The register flow checks `userRepository->existsByEmail()` but an UpdateProfile handler might skip this.
**How to avoid:** UpdateProfile handler must check: if new email != current email → call `existsByEmail()` and throw `UserAlreadyExistsException` if taken.

### Pitfall 6: CS Fixer in GitHub Actions Fails with exit code 8
**What goes wrong:** `php-cs-fixer fix --dry-run` exits with code 8 when there are files that need fixing (not just syntax errors). Some CI configs check for exit code 0 only.
**Why it happens:** CS Fixer uses different exit codes: 0 = OK, 1 = error, 8 = files need fixing.
**How to avoid:** This is the correct behavior — the CI job should fail when code style issues exist. Just ensure the CI step has no `continue-on-error: true` override.

---

## Code Examples

### Adding avatarPath to User entity and Doctrine mapping

```php
// User.php — add field and methods
private ?string $avatarPath;

public function updateProfile(string $firstName, string $lastName, Email $email): void
{
    $this->firstName = $firstName;
    $this->lastName = $lastName;
    $this->email = $email->value();
    $this->updatedAt = new \DateTimeImmutable();
}

public function setAvatarPath(?string $path): void
{
    $this->avatarPath = $path;
    $this->updatedAt = new \DateTimeImmutable();
}

public function avatarPath(): ?string
{
    return $this->avatarPath;
}
```

```xml
<!-- User.orm.xml — add field -->
<field name="avatarPath" type="string" length="500" column="avatar_path" nullable="true"/>
```

### Migration

```php
// Version20260301120000.php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE identity_users ADD COLUMN avatar_path VARCHAR(500) DEFAULT NULL');
}

public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE identity_users DROP COLUMN avatar_path');
}
```

### Controller endpoints

```php
// AuthController.php — add two new routes
#[Route('/me', name: 'update_profile', methods: ['PUT'])]
public function updateProfile(Request $request, #[CurrentUser] SecurityUser $user): JsonResponse
{
    $data = $this->decodeJson($request);
    $this->commandBus->dispatch(new UpdateProfileCommand(
        userId: $user->getId(),
        firstName: $data['first_name'] ?? '',
        lastName: $data['last_name'] ?? '',
        email: $data['email'] ?? '',
    ));
    return new JsonResponse(['message' => 'Profile updated.']);
}

#[Route('/me/avatar', name: 'upload_avatar', methods: ['POST'])]
public function uploadAvatar(Request $request, #[CurrentUser] SecurityUser $user): JsonResponse
{
    $file = $request->files->get('avatar');
    if (null === $file) {
        return new JsonResponse(['error' => 'No file uploaded.'], Response::HTTP_BAD_REQUEST);
    }
    $content = file_get_contents($file->getPathname());
    if (false === $content) {
        return new JsonResponse(['error' => 'Failed to read file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $this->commandBus->dispatch(new UploadAvatarCommand(
        userId: $user->getId(),
        fileContent: $content,
        originalName: $file->getClientOriginalName(),
    ));
    return new JsonResponse(['message' => 'Avatar uploaded.']);
}
```

### Frontend ProfilePage store extension

```typescript
// auth.store.ts additions
async function updateProfile(data: { firstName: string; lastName: string; email: string }) {
  const { default: httpClient } = await import('@/shared/api/http-client')
  await httpClient.put('/auth/me', {
    first_name: data.firstName,
    last_name: data.lastName,
    email: data.email,
  })
  await fetchUser() // refresh user data including avatarUrl
}

async function uploadAvatar(file: File) {
  const { default: httpClient } = await import('@/shared/api/http-client')
  const formData = new FormData()
  formData.append('avatar', file)
  await httpClient.post('/auth/me/avatar', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  await fetchUser() // refresh to get new presigned avatarUrl
}

async function changePassword(currentPassword: string, newPassword: string) {
  const { default: httpClient } = await import('@/shared/api/http-client')
  await httpClient.put('/auth/password', {
    current_password: currentPassword,
    new_password: newPassword,
  })
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| husky for pre-commit | lefthook | ~2022 | lefthook is language-agnostic, faster, and works without node_modules in root |
| `getClientMimeType()` for upload validation | `finfo_buffer()` on file content | Best practice always | Security: prevents MIME spoofing |
| Storing full S3 URLs in DB | Storing S3 key, generating presigned URL on read | Best practice always | Security: URLs expire, keys are permanent |
| GitHub Actions v1/v2 actions | v4 for checkout, setup-php v2, setup-node v4 | 2023-2024 | Required for Node 20+, PHP 8.4 |

**Deprecated/outdated:**
- `actions/checkout@v2`: Use `@v4` — v2 is unmaintained and has known issues with newer runners.
- `shivammathur/setup-php@v1`: Use `@v2` for PHP 8.4 support.

---

## Open Questions

1. **Email change: should it require re-verification?**
   - What we know: The current register flow sends an activation email; PROF-01 says "user can edit email" with no mention of re-verification.
   - What's unclear: Whether changing email should invalidate the session / require re-activation.
   - Recommendation: Keep it simple per requirements — update email directly, no re-verification. Document this as a known limitation for v3.0.

2. **lefthook: install globally or as npm devDependency?**
   - What we know: lefthook can be installed via npm, brew, or directly from GitHub releases. The project root has no `package.json`.
   - What's unclear: Whether to add a root `package.json` for lefthook or use `npx lefthook`.
   - Recommendation: Add `lefthook` as a devDependency in the root `package.json` (create one) and add an `"install"` script that runs `lefthook install`. Simpler for new developers than requiring a global install.

3. **Avatar display in employee lists: does the employee DTO carry userId?**
   - What we know: `TaskComments.vue` shows `comment.authorName || comment.authorId` with `pi-user` icon (no Avatar component there). Employee lists use initials via inline logic.
   - What's unclear: Whether the planner should include updating employee/comment DTOs to carry `avatarUrl` in Phase 7 or defer to Phase 8 (Audit + Activity timeline).
   - Recommendation: Include updating `CommentDTO` and the comment API to carry `avatarUrl` (nullable) in Phase 7, since PROF-03 explicitly requires "comments" as a display location. Employee list avatar requires the employee DTO to link to a user — check if `DoctrineSearchUsersHandler` already joins identity_users.

---

## Validation Architecture

> `workflow.nyquist_validation` is not present in `.planning/config.json` (only `workflow.research`, `workflow.plan_check`, `workflow.verifier`). Skipping this section.

---

## Sources

### Primary (HIGH confidence)
- Direct codebase inspection — `backend/src/Identity/`, `backend/src/TaskManager/Infrastructure/Storage/S3FileStorage.php`, `backend/config/services.yaml`, `frontend/src/modules/auth/stores/auth.store.ts`, `frontend/src/shared/components/AppTopbar.vue`, `docker/php/Dockerfile`
- `backend/composer.json` — confirms `league/flysystem-aws-s3-v3 ^3.32`, `friendsofphp/php-cs-fixer ^3.94`, `phpstan/phpstan ^2.1`, `phpunit/phpunit ^13.0` all installed
- `frontend/package.json` — confirms `eslint ^9.39.2`, `vue-tsc ^3.2.4`, `oxlint`, `primevue ^4.5.4`
- `docker/php/Dockerfile` — confirms `fileinfo` extension is installed via `docker-php-ext-install gd` (fileinfo is bundled with gd config) and Docker image runs PHP 8.4-fpm

### Secondary (MEDIUM confidence)
- PrimeVue Avatar component docs — `image` prop exists for photo URL, `label` prop for initials fallback (consistent with code observed in TaskDetailSidebar.vue using `:label="initials"`)
- lefthook `stage_fixed: true` behavior — documented in lefthook README; re-stages auto-fixed files before commit completes
- GitHub Actions `shivammathur/setup-php@v2` — widely used action for PHP 8.4 CI, confirmed working with fileinfo and amqp extensions

### Tertiary (LOW confidence)
- lefthook installation via root `package.json` recommendation — based on ecosystem patterns; alternative (global install or brew) is equally valid

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all tools already exist in the project; no new packages needed on backend; only lefthook is new
- Architecture: HIGH — avatar upload pattern is a near-exact clone of TaskAttachment upload in TaskManager; profile edit follows ChangePassword handler pattern exactly
- Pitfalls: HIGH — finfo_buffer vs getClientMimeType is a known security pattern; presigned URL TTL is observable from existing S3FileStorage code; CI pitfalls are standard GitHub Actions knowledge
- CI/CD config: MEDIUM — yaml syntax verified against standard patterns, but not executed against actual project

**Research date:** 2026-03-01
**Valid until:** 2026-04-01 (stable tooling — 30-day window appropriate)
