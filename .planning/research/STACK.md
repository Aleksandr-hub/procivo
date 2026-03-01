# Stack Research

**Domain:** BPM Platform — Production-Ready Features (v2.0 Milestone)
**Researched:** 2026-03-01
**Confidence:** HIGH for most items — verified against official docs, packagist, and existing codebase

---

## Context: What Is Already Installed

This is a subsequent-milestone research. The core stack is fixed. This document covers ONLY what needs to be added or changed for v2.0 features.

**Already installed (do not re-add):**

Backend (`/Users/leleka/Projects/procivo/backend/composer.json`):
- `symfony/mailer: 8.0.*` — already in composer.json
- `symfony/amqp-messenger: 8.0.*` — already in composer.json
- `symfony/messenger: 8.0.*` — already in composer.json, 3-bus CQRS setup
- `symfony/mercure-bundle: ^0.4.2` — already in composer.json
- `symfony/security-bundle: 8.0.*` — already in composer.json
- `league/flysystem-aws-s3-v3: ^3.32` — already in composer.json
- `aws/aws-sdk-php: ^3.x` — already installed as **transitive dependency** via flysystem-aws-s3-v3 (confirmed: `/vendor/aws/aws-sdk-php` exists, latest 3.371.3 as of 2026-02-27)
- `symfony/workflow: 8.0.*` — already in composer.json
- `symfony/expression-language: 8.0.*` — already in composer.json

Frontend (`/Users/leleka/Projects/procivo/frontend/package.json`):
- `primevue: ^4.5.4` — already in package.json (Chart component built-in, needs chart.js added)
- `pinia: ^3.0.4` — already in package.json
- `zod: ^4.3.6` — already in package.json
- `axios: ^1.13.5` — already in package.json

---

## What Needs to Be Added for v2.0

### Backend — New Packages

| Package | Version | Feature | Why |
|---------|---------|---------|-----|
| `symfony/scheduler` | `8.0.*` | Timer node execution | Built-in Symfony component for recurring/scheduled dispatch. Required to implement timer node execution in the Workflow Engine. Dispatcher creates `RecurringMessage` or dispatches via `ScheduledStamp`. Replaces need for external cron jobs. [HIGH confidence — official Symfony 8 docs] |
| `knplabs/knp-paginator-bundle` | `^6.6` | Paginated lists (audit log, task history, notifications) | Standard Symfony pagination bundle for Doctrine queries. Provides `PaginatorInterface` compatible with Doctrine's `QueryBuilder`. Supports LIMIT/OFFSET, total count, page calculation. Used for audit log timeline and notification list. [MEDIUM confidence — packagist verified, widely used] |

**Note on `aws/aws-sdk-php`:** Already available through `league/flysystem-aws-s3-v3`. No need to add directly. For presigned PUT URLs (avatar upload), use `$s3Client->createPresignedRequest()` directly — the S3Client is injectable via Symfony DI. [HIGH confidence — aws docs verified]

**Note on `symfony/mailer`:** Already installed. For async email via RabbitMQ: route `Symfony\Component\Mailer\Messenger\SendEmailMessage` to the existing `amqp` transport in `messenger.yaml`. Zero new packages needed. [HIGH confidence — Symfony docs verified]

**Note on `symfony/security-bundle` switch_user:** Already installed. However, `switch_user` firewall listener is **NOT compatible with stateless JWT authentication** (per official Symfony docs). See impersonation section below for the workaround. [HIGH confidence — official Symfony docs]

### Frontend — New Packages

| Package | Version | Feature | Why |
|---------|---------|---------|-----|
| `chart.js` | `^4.4.9` | Dashboard charts | PrimeVue 4 Chart component is a thin wrapper around Chart.js — it does NOT bundle Chart.js. Must be installed separately. PrimeVue docs: "Chart component uses Chart.js underneath so it needs to be installed as a dependency." [HIGH confidence — PrimeVue 4 official docs] |

### Development Tools — New

| Tool | Type | Feature | Why |
|------|------|---------|-----|
| `lefthook` | devDependency (npm) or global binary | Pre-commit hooks | Language-agnostic Git hook manager written in Go. Runs PHP CS Fixer, ESLint, TypeScript check, PHPStan on staged files. Preferred over Husky because: no Node.js dependency on PHP-only machines, parallel execution, single YAML config covering both backend and frontend. Config in `lefthook.yml` at repo root. [MEDIUM confidence — multiple sources, actively maintained 2026] |
| `shivammathur/setup-php@v2` | GitHub Actions marketplace | CI/CD PHP setup | Standard action for PHP 8.4 in GitHub Actions. Supports extensions, composer, PHP version matrix. Used in every Symfony CI/CD tutorial 2025-2026. [HIGH confidence — widely documented] |
| `actions/cache@v4` | GitHub Actions marketplace | CI/CD Composer cache | Cache `~/.composer/cache` keyed by `composer.lock` hash to avoid re-downloading packages on each run. [HIGH confidence — official GitHub Actions docs] |

---

## Recommended Stack

### Core Backend Technologies (New for v2.0)

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| `symfony/scheduler` | `8.0.*` | Timer node execution in WorkflowEngine | Symfony 8 built-in component for managing scheduled/recurring task dispatch. For Timer nodes: when a Timer node is activated, persist its target fire time, then have a Scheduler `RecurringMessage` poll for overdue timers every minute and dispatch `TimerFiredCommand`. Uses Symfony Messenger transport underneath — integrates naturally with existing 3-bus CQRS setup. Does not require external cron. [HIGH confidence — symfony.com/doc/current/scheduler.html verified] |
| Symfony Messenger `DelayStamp` | `8.0.*` (already installed) | Timer node — single fire delay | For known fixed durations (e.g., "wait 3 days"), dispatch `TimerFiredCommand` with `new DelayStamp($milliseconds)` to the `async` AMQP transport. RabbitMQ Messenger creates a TTL delay queue with DLX automatically via `auto_setup: true` — no plugin needed. For absolute date timers ("fire at 2026-04-15"), use Scheduler poll approach instead. [HIGH confidence — Symfony Messenger docs, DLX/TTL mechanism verified] |
| Symfony Messenger `SendEmailMessage` routing | `8.0.*` (already installed) | Async email notifications | `symfony/mailer` already installed. Route `Symfony\Component\Mailer\Messenger\SendEmailMessage` to the existing `amqp` transport in `messenger.yaml`. Emails dispatched via `$mailer->send()` automatically go async. Zero new packages. [HIGH confidence — Symfony Mailer async docs verified] |
| `aws/aws-sdk-php` via flysystem | `^3.371` (transitive) | Avatar presigned upload URL | `aws/aws-sdk-php` is already installed as a transitive dependency. Use `S3Client::createPresignedRequest(PutObject command, '+15 minutes')` to generate upload URLs. Frontend uploads directly to S3/LocalStack — backend never handles binary data. [HIGH confidence — aws PHP SDK v3 docs, `vendor/aws/` confirmed present] |

### Core Frontend Technologies (New for v2.0)

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| `chart.js` | `^4.4.9` | Dashboard bar/line/doughnut charts | PrimeVue 4 `<Chart>` component wraps Chart.js but does NOT bundle it. Must install separately. Chart.js 4.x is the stable current major (v5 alpha exists but not stable). PrimeVue Chart supports: Bar, Line, Doughnut, Pie, Radar, PolarArea. For dashboard: use Bar (tasks per status), Line (process completions over time), Doughnut (task distribution). [HIGH confidence — primevue.org/chart/ docs verified] |

### Supporting Backend Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `knplabs/knp-paginator-bundle` | `^6.6` | Pagination for audit log, notification inbox | When implementing `GET /audit-logs?page=N` and `GET /notifications?page=N`. Wraps Doctrine `QueryBuilder` — call `$paginator->paginate($query, $page, $limit)`. Returns `PaginationInterface` with total count and page data. Alternative: manual LIMIT/OFFSET with COUNT query — acceptable for v2.0 at pet project scale, but Paginator is 3 lines vs 20. [MEDIUM confidence — packagist, widely used in Symfony ecosystem] |
| Doctrine `json` type (already in use) | `3.6.*` | `audit_log.context` JSONB column | Already used throughout project. `audit_log` table needs a `context` JSONB column for storing event payload (old/new values, actor info). Use existing pattern. No new library. [HIGH confidence] |

### Supporting Frontend Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PrimeVue `<Chart>` component (already installed) | `4.5.4` | Chart rendering wrapper | Already available in PrimeVue 4 — just import `import { Chart } from 'primevue'`. Requires `chart.js` installed separately. Provides Vue-idiomatic props (`type`, `data`, `options`), reactive updates. Use in `DashboardPage.vue`. [HIGH confidence] |
| PrimeVue `<FileUpload>` component (already installed) | `4.5.4` | Avatar upload UI | PrimeVue 4 `<FileUpload>` supports `customUpload` mode — intercept the upload event, POST to backend for presigned URL, then PUT binary to S3 directly from browser. No server round-trip for binary data. [HIGH confidence] |
| PrimeVue `<Badge>` + `<OverlayPanel>` (already installed) | `4.5.4` | Notification bell icon | `<Badge>` for unread count overlay on bell icon. `<OverlayPanel>` for notification dropdown. Both already in PrimeVue 4. No new library. [HIGH confidence] |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| `lefthook` | Pre-commit hooks — runs CS Fixer + ESLint on staged files | Install via `npm install --save-dev lefthook` or `brew install lefthook`. Config in `lefthook.yml` at repo root. Runs PHP CS Fixer (`php-cs-fixer fix --dry-run`) on staged `.php` files, ESLint + TypeScript on staged `.ts/.vue` files. Faster than Husky (Go binary, parallel execution). [MEDIUM confidence] |
| `shivammathur/setup-php@v2` | GitHub Actions — PHP 8.4 environment setup | Use in `.github/workflows/ci.yml`. Installs PHP 8.4, composer, required extensions (pgsql, amqp, redis). Standard action for Symfony CI in 2025-2026. [HIGH confidence] |
| `actions/cache@v4` | GitHub Actions — Composer and npm cache | Cache key: `${{ runner.os }}-composer-${{ hashFiles('backend/composer.lock') }}`. Reduces CI time from ~3 min to ~45 sec. [HIGH confidence] |
| `ramsey/composer-install` | GitHub Actions — composer install with built-in caching | Alternative to manual `actions/cache` for composer. Single action handles cache key management. Either approach works. [MEDIUM confidence] |

---

## Installation

### Backend

```bash
# New packages only
cd /Users/leleka/Projects/procivo/backend

# Timer node execution
composer require symfony/scheduler:"8.0.*"

# Pagination (optional — can defer to when lists are implemented)
composer require knplabs/knp-paginator-bundle:"^6.6"

# Note: aws/aws-sdk-php is ALREADY installed via league/flysystem-aws-s3-v3
# Note: symfony/mailer is ALREADY installed
# Note: symfony/scheduler may already be a transitive dep — check first:
composer show symfony/scheduler 2>/dev/null || echo "not installed"
```

### Frontend

```bash
# New packages only
cd /Users/leleka/Projects/procivo/frontend

# Chart.js (required by PrimeVue Chart component)
npm install chart.js

# Pre-commit hooks
npm install --save-dev lefthook
```

### GitHub Actions (no npm/composer install)

Create `.github/workflows/ci.yml` using:
- `actions/checkout@v4`
- `shivammathur/setup-php@v2` (PHP 8.4, extensions: pdo_pgsql, amqp, redis, intl)
- `actions/cache@v4` (composer.lock hash key)
- `actions/setup-node@v4` (Node 24)
- `actions/cache@v4` (package-lock.json hash key)

---

## Feature-Specific Integration Notes

### Audit Logging (async domain event → audit_log table)

**Pattern:** Existing `event.bus` already routes domain events. Add `AuditLogSubscriber` that listens on the event bus and persists to `audit_log` table.

```yaml
# messenger.yaml addition
routing:
  'App\Shared\Domain\Event\AuditableEventInterface': audit_transport
```

**No new library needed.** Use existing Doctrine + Messenger async pattern. The `audit_log` table needs: `id`, `event_type`, `actor_id`, `entity_type`, `entity_id`, `context` (JSONB), `occurred_at`.

### Notification System (in-app + email)

**In-app:** Existing `symfony/mercure-bundle` already installed. Mercure hub at port 3000. `NotificationCreatedEvent` → event.bus → `MercureNotificationPublisher` → browser.

**Email:** `symfony/mailer` already installed. Route `SendEmailMessage` to `amqp` transport:
```yaml
# messenger.yaml addition
routing:
  'Symfony\Component\Mailer\Messenger\SendEmailMessage': async
```

**No new Symfony bundle needed.** The existing `Notification` module (confirmed in PROJECT.md: "Notifications module with Mercure real-time updates — Phase 3") just needs to be extended.

### Dashboard Charts

**PrimeVue Chart component** is already available in PrimeVue 4. Only `chart.js` npm package needs to be added. For dashboard data, create a `GET /dashboard/summary` query endpoint returning aggregated counts:
- tasks by status
- active processes count
- recent completions (7/30 day window)

### User Profile + Avatar Upload (S3)

**Backend approach:**
1. `POST /users/me/avatar/upload-url` → returns presigned PUT URL (15 min expiry) + final CDN URL
2. Frontend PUTs binary directly to S3/LocalStack
3. `POST /users/me/avatar/confirm` → marks avatar as active in DB

```php
// S3Client is already injectable via Flysystem's registered service
// Or register directly:
$s3 = new S3Client(['endpoint' => getenv('AWS_ENDPOINT'), 'region' => 'us-east-1', ...]);
$cmd = $s3->getCommand('PutObject', ['Bucket' => $bucket, 'Key' => $key]);
$request = $s3->createPresignedRequest($cmd, '+15 minutes');
$presignedUrl = (string) $request->getUri();
```

**No new Symfony bundle needed.** `aws/aws-sdk-php` is already present in vendor.

### Timer Node Execution

**Strategy:** Two-part approach depending on timer type.

**Duration timers** (e.g., "wait 2 hours"): Dispatch `TimerFiredCommand` with `DelayStamp`:
```php
$this->commandBus->dispatch(
    new TimerFiredCommand($tokenId, $nodeId),
    [new DelayStamp($durationMs)]
);
```
Symfony Messenger AMQP transport automatically creates a TTL delay queue with Dead Letter Exchange — no plugin needed. [HIGH confidence — built-in since Symfony 4.2, verified for 4.x/RabbitMQ 4.2]

**Date timers** (e.g., "fire at 2026-04-15 09:00"): Use `symfony/scheduler`:
```php
// ScheduleProvider: check for overdue timer tokens every minute
RecurringMessage::every('1 minute', new CheckOverdueTimersMessage())
```
[HIGH confidence — scheduler.html official docs]

**Critical note on RabbitMQ delayed plugin:** The `rabbitmq-delayed-message-exchange` plugin was **archived on January 29, 2026** and will break when RabbitMQ drops Mnesia (planned 4.3 or 4.4). Do NOT use it. Symfony's built-in AMQP delay queue mechanism is the correct approach. [HIGH confidence — official rabbitmq/rabbitmq-delayed-message-exchange GitHub, archived status verified]

### Super Admin Impersonation

**Problem:** Symfony's built-in `switch_user` firewall listener is **incompatible with stateless JWT authentication** (per official Symfony 8 docs). It relies on session state, which JWT APIs don't have.

**Solution — Custom JWT Impersonation Endpoint:**
```
POST /admin/impersonate
Body: { "userId": "uuid" }
Headers: Authorization: Bearer <super-admin-JWT>
Response: { "token": "<impersonated-user-JWT>", "originalToken": "<super-admin-JWT>" }
```

Implementation:
1. `ROLE_SUPER_ADMIN` guard on endpoint
2. Load target `User` entity
3. Use `lexik/jwt-authentication-bundle`'s `JWTManager::create($user)` to generate a short-lived token
4. Add custom `impersonated_by` claim to JWT payload
5. Frontend stores both tokens, shows "Exit Impersonation" banner

**Do NOT use `switch_user: true` in `security.yaml`** — it will silently fail with JWT stateless firewalls.

[HIGH confidence — official Symfony impersonating_user.html docs clearly state incompatibility with stateless auth; LexikJWT issue #652 and #1196 confirm no native support]

### CI/CD Pipeline (GitHub Actions)

**Recommended workflow structure** (`.github/workflows/ci.yml`):

```yaml
jobs:
  backend:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:18
      redis:
        image: redis:8
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql, redis, intl, amqp
      - uses: actions/cache@v4  # composer cache
      - run: composer install --no-dev --optimize-autoloader
      - run: vendor/bin/php-cs-fixer check
      - run: vendor/bin/phpstan analyse
      - run: vendor/bin/phpunit

  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '24' }
      - uses: actions/cache@v4  # npm cache
      - run: npm ci
      - run: npm run type-check
      - run: npm run lint
      - run: npm run test:unit
```

[HIGH confidence — shivammathur/setup-php@v2 is the de-facto standard, multiple 2025-2026 sources]

### Pre-commit Hooks (lefthook)

**lefthook.yml** at repo root:
```yaml
pre-commit:
  parallel: true
  commands:
    php-cs-fixer:
      glob: "backend/**/*.php"
      run: cd backend && vendor/bin/php-cs-fixer fix {staged_files} --config=.php-cs-fixer.php
    eslint:
      glob: "frontend/**/*.{ts,vue}"
      run: cd frontend && npx eslint {staged_files} --fix
    typescript:
      glob: "frontend/**/*.{ts,vue}"
      run: cd frontend && npm run type-check

commit-msg:
  commands:
    conventional:
      run: echo "{1}" | grep -qE "^(feat|fix|chore|docs|refactor|test|style|perf|ci)(\(.+\))?: .+" || (echo "Commit message must follow conventional commits" && exit 1)
```

[MEDIUM confidence — lefthook docs, PHP CS Fixer staged files support verified]

---

## Alternatives Considered

| Recommended | Alternative | Why Not |
|-------------|-------------|---------|
| `symfony/scheduler` for timer nodes | External cron job calling Symfony console command | Scheduler runs inside Symfony process, uses Messenger transport, no OS-level cron dependency, testable, visible in Messenger workers. External cron adds operational complexity. |
| Symfony Messenger built-in AMQP delay (DLX/TTL) | `rabbitmq-delayed-message-exchange` plugin | Plugin archived January 2026, incompatible with RabbitMQ 4.3+ (Khepri migration). Built-in DLX/TTL is production-ready, uses standard RabbitMQ features, no plugin dependency. |
| Custom JWT impersonation endpoint | Symfony `switch_user` firewall | `switch_user` is session-based and explicitly documented as incompatible with stateless JWT auth. Custom endpoint gives full control over token claims and expiry. |
| `lefthook` for pre-commit hooks | Husky + lint-staged | Lefthook is written in Go (faster), language-agnostic (no Node.js runtime needed for PHP devs), single YAML config for polyglot repo (PHP + TypeScript), parallel execution out of the box. Husky still valid but Lefthook is the 2025-2026 recommendation for multi-language projects. |
| `chart.js@^4.4.9` with PrimeVue Chart wrapper | vue-chartjs or react-chartjs-2 | PrimeVue 4's `<Chart>` component IS the Vue wrapper — no need for vue-chartjs. Using vue-chartjs would duplicate the wrapper layer. Chart.js 4.x is stable; v5 alpha not ready for production. |
| `knplabs/knp-paginator-bundle` | Manual LIMIT/OFFSET | For v2.0 at pet project scale, manual pagination is acceptable. KnpPaginator is the standard choice and reduces boilerplate. Can defer to when lists are actually implemented. |

---

## What NOT to Add

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| `rabbitmq-delayed-message-exchange` Docker plugin | Archived January 2026, incompatible with RabbitMQ 4.3+ (Mnesia removal). Will break on next RabbitMQ upgrade. | Symfony Messenger built-in AMQP delay queues with `DelayStamp` |
| `Symfony\Component\Security\Http\Firewall\SwitchUserListener` (switch_user: true) | Explicitly incompatible with stateless JWT firewalls per official Symfony docs | Custom `POST /admin/impersonate` endpoint using `JWTManager::create()` |
| `vich/uploader-bundle` | Adds server-side binary upload handling overhead. Procivo already uses S3 directly via Flysystem. Vich adds its own entity lifecycle hooks that conflict with DDD aggregate approach. | Direct `aws/aws-sdk-php` presigned URL + Flysystem for storage |
| `chart.js@^5.0` (alpha) | Version 5 is alpha as of March 2026, not stable, PrimeVue 4 Chart component explicitly targets v4 | `chart.js@^4.4.9` |
| `GrumPHP` | PHP-only Git hook manager. Procivo is a polyglot repo (PHP + TypeScript + Vue). GrumPHP cannot run ESLint/TypeScript checks without hacks. | `lefthook` (language-agnostic) |
| Separate `audit-trail` third-party bundle | Adds migration complexity, conflicts with custom DDD event model. Procivo's event.bus already dispatches domain events — just subscribe and persist. | Custom `AuditLogSubscriber` + `audit_log` table |
| `aws/aws-sdk-php` as direct composer dependency | Already transitively installed via `league/flysystem-aws-s3-v3`. Adding it directly creates version conflict risk. | Use the existing transitive installation; access `S3Client` directly or via Flysystem's registered service |

---

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| `symfony/scheduler: 8.0.*` | Symfony 8.0.*, PHP 8.4, `symfony/messenger: 8.0.*` | Must use same Symfony minor as the rest of the project. Uses Messenger under the hood — workers run via `bin/console messenger:consume scheduler_*`. |
| `chart.js: ^4.4.9` | `primevue: ^4.5.4`, Vue 3.5.x | PrimeVue 4 `<Chart>` component wraps Chart.js 4.x. Chart.js 3.x is NOT compatible with PrimeVue 4's Chart API. Do not use ^3. |
| `knplabs/knp-paginator-bundle: ^6.6` | Symfony 8.0.*, Doctrine ORM 3.6 | KnpPaginator 6.x requires Symfony 7+/8+. Version 5.x was for Symfony 5/6. [MEDIUM confidence — packagist page] |
| `lefthook` (any recent) | Node 24, PHP 8.4 | Language-agnostic — no runtime dependency. Works with any PHP/Node version. Config in repo root `lefthook.yml`. |
| `aws/aws-sdk-php: ^3.371` (transitive) | PHP 8.1+, PHP 8.4 compatible | Already installed. `createPresignedRequest` API unchanged since v3.x. [HIGH confidence] |

---

## Sources

- Official Symfony 8 Scheduler docs — https://symfony.com/doc/current/scheduler.html — confirms it's for recurring tasks, uses Messenger, available since 6.3 [HIGH confidence]
- Official Symfony 8 Messenger docs — https://symfony.com/doc/current/messenger.html — `DelayStamp`, AMQP auto-setup, delay queue DLX/TTL mechanism [HIGH confidence]
- Official Symfony Security docs — https://symfony.com/doc/current/security/impersonating_user.html — confirmed JWT stateless incompatibility with `switch_user` [HIGH confidence]
- Official PrimeVue 4 Chart docs — https://primevue.org/chart/ — confirmed Chart.js peer dependency required [HIGH confidence]
- `rabbitmq/rabbitmq-delayed-message-exchange` GitHub — https://github.com/rabbitmq/rabbitmq-delayed-message-exchange — archived January 29, 2026, Mnesia-dependent, recommended against for 4.3+ [HIGH confidence]
- AWS SDK PHP v3 presigned URLs — https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-presigned-url.html — `createPresignedRequest` API [HIGH confidence]
- `packagist.org/packages/aws/aws-sdk-php` — latest stable 3.371.3 (2026-02-27), requires PHP >=8.1 [HIGH confidence]
- `shivammathur/setup-php@v2` GitHub Actions — https://github.com/marketplace/actions/setup-php-action — standard Symfony CI action [HIGH confidence]
- Lefthook vs Husky 2026 comparison — https://www.edopedia.com/blog/lefthook-vs-husky/ — performance and polyglot advantages [MEDIUM confidence]
- LexikJWT impersonation issues — https://github.com/lexik/LexikJWTAuthenticationBundle/issues/652 — confirmed no native switch_user support [MEDIUM confidence]
- Codebase analysis — `/Users/leleka/Projects/procivo/backend/composer.json` and `frontend/package.json` — existing dependencies verified [HIGH confidence]

---
*Stack research for: Procivo BPM — v2.0 Production-Ready Features*
*Researched: 2026-03-01*
