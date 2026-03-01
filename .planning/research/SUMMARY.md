# Project Research Summary

**Project:** Procivo BPM Platform — v2.0 Production-Ready Milestone
**Domain:** Business Process Management (BPM) — operational features on top of shipped v1.0 engine
**Researched:** 2026-03-01
**Confidence:** HIGH

## Executive Summary

Procivo v1.0 delivered a complete BPM loop (design → publish → start → tasks → XOR routing → complete) on top of a mature Modular Monolith with Clean Architecture, DDD, CQRS, and three Messenger buses. The v2.0 milestone is not about reinventing the platform — it is about closing the gap between a working prototype and a production-ready tool. Research across four dimensions (stack, features, architecture, pitfalls) consistently converges on the same seven operational concerns that every mature BPM platform (Camunda 8, Flowable, IBM BPM, ProcessMaker) provides at this layer: audit trail, notifications, dashboard, user profiles, timer node execution, super admin impersonation, and CI/CD automation. All of these can be built without adding major infrastructure — the core stack (Symfony 8, RabbitMQ, Mercure, S3, PostgreSQL) already supports every required pattern.

The recommended approach is incremental: start with the two features that everything else depends on (user profile/avatar and audit logging), then layer notifications, dashboard, and timer execution on top of that foundation. The existing domain event infrastructure (`event.bus`, `DispatchDomainEventsMiddleware`, async transport) is the backbone of this milestone — audit logging is a pure event consumer, notifications extend an existing module, and the dashboard reads from tables already populated by existing projections. Timer node execution is nearly complete in code; the work is primarily verification, testing, and adding a persistent fallback table. CI/CD is pure infrastructure that can be set up independently at any time and should be established early.

The top risks are architectural traps that appear correct but fail in production: writing audit logs inside the business transaction (they must be async), using Symfony's built-in `switch_user` for impersonation (incompatible with stateless JWT firewalls), relying solely on RabbitMQ `DelayStamp` for timer reliability (plugin archived, messages lost on restart), and broadcasting Mercure notifications on organization-wide topics (leaks private content to other users). Each pitfall has a documented prevention strategy that must be applied from the start of the relevant phase — retrofitting is expensive.

---

## Key Findings

### Recommended Stack

The core stack requires only two net-new packages for v2.0: `symfony/scheduler` (timer node date-based execution) and `chart.js` (required peer dependency for PrimeVue's Chart component). Everything else is already installed. The `aws/aws-sdk-php` SDK needed for S3 presigned avatar upload URLs is already present as a transitive dependency via `league/flysystem-aws-s3-v3`. Symfony Mailer, Mercure bundle, and RabbitMQ Messenger are all in place for async email and real-time notifications. Development tooling additions (lefthook for pre-commit hooks, GitHub Actions actions) require no composer/npm changes beyond one `devDependency`.

**Core technologies (new for v2.0):**
- `symfony/scheduler 8.0.*`: Date-based timer node execution — polls for overdue timers every minute via Symfony Messenger; no external cron required
- `chart.js ^4.4.9`: Dashboard chart rendering — PrimeVue 4 `<Chart>` component wraps it but does NOT bundle it; must be installed separately
- Symfony Messenger `DelayStamp` (already installed): Duration-based timer delays via RabbitMQ Dead Letter Exchange/TTL — replaces the archived `rabbitmq-delayed-message-exchange` plugin
- `lefthook` (devDependency): Pre-commit hooks for polyglot repo (PHP + TypeScript) — Go binary, parallel execution, single YAML config
- `shivammathur/setup-php@v2` + `actions/cache@v4`: GitHub Actions PHP 8.4 CI environment — industry standard for Symfony projects in 2025-2026

**Critical version constraints:**
- Do NOT use `chart.js ^5.0` (alpha, not stable, PrimeVue 4 targets v4)
- Do NOT add `rabbitmq-delayed-message-exchange` Docker plugin (archived January 29, 2026; breaks on RabbitMQ 4.3+ Mnesia removal)
- Do NOT use `switch_user: true` in security.yaml (incompatible with stateless JWT firewall; documented in official Symfony docs and LexikJWT issues #652/#1196)

---

### Expected Features

Research compared Procivo's planned features against Camunda 8, Flowable, IBM BPM, ProcessMaker, and Oracle BPM. The seven feature categories each have a clear table-stakes baseline and a set of v2.0-appropriate differentiators.

**Must have (table stakes for v2.0):**
- Async audit logging of task lifecycle, process lifecycle, auth events, and admin operations — every compliance-oriented BPM platform provides this
- Task-assigned notification (in-app via Mercure + email via Symfony Mailer) — without this, users must poll the task list; the single most impactful notification
- Bell icon with unread count + notification inbox page — the visible surface for all in-app notifications
- Dashboard: "My Tasks", "Active Processes I Started", "Recent Activity" feed — home screen context on login
- Avatar upload (S3 presigned URL flow) + display on task cards and navigation bar
- Timer node execution: duration timers (ISO 8601 `PT1H`, `P1D`) and date timers (absolute datetime) — core BPMN feature; currently unimplemented at execution level
- Super admin impersonation with persistent banner, exit button, and audit log entry — support capability for any multi-user deployment
- GitHub Actions CI pipeline: CS Fixer + PHPStan + PHPUnit + frontend type-check/lint

**Should have (v2.0 differentiators):**
- Per-user notification preferences (opt-in/out per event type and channel) — prevents email flood, which is a critical anti-pattern
- Process completed notification to initiator
- Dashboard team workload widget (manager view) with bar chart via Chart.js
- Impersonation reason field (logged in audit entry) — low complexity, high accountability value
- Pre-commit hooks (lefthook) for CS Fixer + ESLint on staged files

**Defer to v2.x / v3.0:**
- Timer Boundary Events on task nodes (deadline/escalation pattern) — architectural complexity significantly higher than intermediate timers
- Notification digest/grouping — scheduling state and grouping logic are a separate scope
- @-mention notifications in comments — text parsing + fan-out
- Playwright e2e smoke tests in CI — high setup cost; add after CI is stable
- Time-limited impersonation sessions — custom Symfony middleware
- Dashboard process cycle time chart — meaningful only after sufficient data accumulates

**Feature dependency order (critical for phase sequencing):**
- Audit Logging must precede Impersonation (impersonation events require an established audit trail)
- Notifications depend on Mercure module (exists) and Mailer (exists)
- Dashboard depends on Audit Logging for the activity feed (reads `audit_log` table)
- User Profile/Avatar is independent but unblocks avatar display in Dashboard, Notifications, and AuditLog UI
- Timer Execution is independent of all above (needs only RabbitMQ + Workflow module, both done)
- CI/CD is fully independent and should be set up early

---

### Architecture Approach

The existing architecture is a well-structured Modular Monolith with 9 modules (Shared, Identity, Organization, TaskManager, Workflow, Notification, Resource, Directory, Search), each following Clean Architecture layers. Cross-module communication uses four established patterns: domain events via `event.bus`, Port/Adapter interfaces for synchronous cross-module queries, direct DBAL in Infrastructure adapters for simple reads, and async routing via RabbitMQ for expensive work. All new v2.0 features fit cleanly into this existing structure — two new modules (AuditLog, Dashboard), two module extensions (Identity for profile/avatar, Notification for email+Mercure push), one completion task (Workflow timer execution), and one pure infrastructure addition (CI/CD).

**Major components (new/extended for v2.0):**
1. `AuditLog` module (NEW) — pure event consumer; subscribes to domain events from all modules on `event.bus` async transport; persists `AuditEntry` with actor, entity, action, JSONB payload, `occurred_at`; exposes `GET /api/v1/audit-log` query endpoint
2. `Notification` module (EXTENDED) — add `NotificationMercurePublisher` for per-user SSE topics (`/users/{userId}/notifications`), `EmailNotificationPort` + `SymfonyEmailNotificationSender`, `NotificationPreference` entity, and new event handlers for `ProcessStarted`/`ProcessCompleted`
3. `Dashboard` module (NEW) — query-only; `GetDashboardSummaryHandler` uses raw DBAL queries against existing tables (`task_manager_tasks`, `workflow_process_instances_view`, `notification`) to avoid cross-module ORM coupling; separate `GetActivityFeedHandler` reads `audit_log`
4. `Identity` module (EXTENDED) — `avatarUrl` field on `User`, `UpdateUserProfileCommand`, `UploadUserAvatarCommand` with S3 presigned URL flow, `FileStorageInterface` port (mirrors TaskManager pattern)
5. `Workflow` module (COMPLETED) — timer infrastructure exists (`RabbitMqTimerService`, `OnTimerScheduled`, `FireTimerHandler`); gaps are: persistent `workflow_scheduled_timers` table as fallback, ISO duration parsing verification, and integration test coverage
6. `.github/workflows/` (INFRA) — `ci.yml` with parallel PHP/frontend jobs using `shivammathur/setup-php@v2`

**Key patterns to enforce:**
- All AuditLog event handlers must be routed to `async` transport BEFORE writing any handler (prevents same-transaction write pitfall)
- Dashboard uses `Connection` (DBAL), never module repositories (prevents bounded context violation and N+1 queries)
- Mercure topics: organization-wide (`/organizations/{orgId}/activity`) for board updates; per-user (`/users/{userId}/notifications`) for personal notifications only
- Actor context must be embedded in domain event payloads (`actorId`, `actorEmail`) — Symfony Security token is null inside async workers

---

### Critical Pitfalls

1. **Audit log writes inside the business transaction** — If `AuditLog` event handlers are not explicitly routed to `async` transport, they execute synchronously within the `doctrine_transaction` middleware scope. An audit write failure rolls back the business command; a business rollback silently drops the audit entry. Prevention: add `App\AuditLog\Application\EventHandler\*: async` to `messenger.yaml` BEFORE registering any audit handler.

2. **Symfony `switch_user` incompatible with stateless JWT firewall** — `switch_user` requires session state that stateless JWT firewalls do not provide. Impersonation works only for a single request; subsequent requests lose impersonation context silently. Prevention: implement `POST /api/v1/admin/impersonate/{userId}` that issues a short-lived impersonation JWT with `impersonated_by` claim; frontend stores separately and shows banner.

3. **RabbitMQ `DelayStamp` as sole timer mechanism** — The `rabbitmq-delayed-message-exchange` plugin was archived January 29, 2026 and will break on RabbitMQ 4.3+ (Mnesia removal). More critically: delayed messages are lost on RabbitMQ restart with no recovery path, leaving process tokens stuck forever. Prevention: create `workflow_scheduled_timers` persistent table as source of truth; `DelayStamp` message is an accelerator only; a Symfony Scheduler job re-dispatches overdue timers every 1-5 minutes as fallback.

4. **Mercure topic leaks between users** — Publishing notifications to the organization-wide topic exposes private notification content (HR decisions, salary approvals) to all org members. Prevention: use per-user topic `/users/{userId}/notifications` exclusively for personal notifications; subscriber JWT must enumerate exact topics, never wildcard.

5. **Actor null in async audit handlers** — Symfony Security context is unavailable inside async Messenger workers. Calling `$security->getUser()` in an async audit handler returns null. Prevention: every domain event that requires audit attribution must carry `actorId` and `actorEmail` as explicit payload fields, set at command dispatch time.

6. **Dashboard crossing module boundaries via ORM repositories** — Injecting `TaskRepositoryInterface` and `ProcessInstanceRepositoryInterface` into a single `DashboardQueryHandler` violates bounded context isolation and hides N+1 query patterns. Prevention: `DashboardQueryHandler` injects only Doctrine `Connection`; raw DBAL queries only; all queries scoped by `organization_id`.

7. **S3 avatar MIME type not validated server-side** — Browser-supplied `Content-Type` is untrusted. `UploadedFile::getMimeType()` reflects client claim, not file content. Prevention: validate with `finfo_buffer()` server-side; enforce 5MB max; generate UUID-based S3 key (never original filename); use public bucket ACL or 24h presigned URL for avatars (the current 1h expiry used for task attachments is wrong for display assets).

---

## Implications for Roadmap

Based on combined research findings, the following phase structure is recommended. Ordering respects feature dependencies, delivers value incrementally, and sequences pitfall prevention before the feature that would trigger each pitfall.

### Phase 1: Foundation — User Profile + CI/CD

**Rationale:** User profile/avatar is self-contained (extends Identity module only), has no upstream dependencies, and unblocks avatar display in every subsequent phase (AuditLog UI, Dashboard task cards, Notification items). CI/CD is fully independent and should be established immediately to provide regression safety as other features are added. These two items deliver visible, demo-able progress with zero architectural risk.

**Delivers:**
- `ProfilePage.vue` with avatar upload via S3 presigned URL flow
- `avatarUrl` on `User` entity, propagated to JWT claims and all relevant DTOs
- `UpdateUserProfileCommand` + `UploadUserAvatarCommand` in Identity module with `FileStorageInterface` port and `S3UserAvatarStorage` implementation
- `.github/workflows/ci.yml` with parallel PHP (CS Fixer + PHPStan + PHPUnit) and frontend (type-check + lint + test:unit) jobs
- `lefthook.yml` pre-commit hooks (CS Fixer on staged PHP, ESLint on staged TS/Vue)

**Addresses:** User Profile + Avatar (table stakes), CI/CD pipeline (table stakes)

**Avoids:** S3 MIME type pitfall — implement `finfo_buffer()` server-side validation and 24h presigned URL expiry for avatar display assets from the start

**Research flag:** Standard patterns — no additional research needed. S3 presigned URL flow mirrors existing `S3FileStorage.php` in TaskManager. `shivammathur/setup-php@v2` is de-facto standard.

---

### Phase 2: Audit Logging

**Rationale:** Audit logging must be built before Super Admin Impersonation (impersonation events require an established audit trail) and before Dashboard (activity feed reads `audit_log`). Building it second ensures the infrastructure is in place for everything that depends on it. The AuditLog module is architecturally low-risk — pure event consumer, no changes to existing modules, follows the established `Notification/Application/EventHandler/` pattern exactly.

**Delivers:**
- New `AuditLog` module with `AuditEntry` entity, Doctrine XML mapping, `DoctrineAuditEntryRepository`
- Event handlers for: `TaskCreated`, `TaskStatusChanged`, `TaskAssigned`, `ProcessStarted`, `ProcessCompleted`, `ProcessCancelled`, `CommentAdded`, authentication events (login, logout, failed attempt)
- `audit_log` table with BRIN index on `occurred_at`, no FK constraints, monthly range partitioning (initial migration must include schema)
- `messenger.yaml` routing additions for all audit-relevant events to `async` transport
- `GET /api/v1/audit-log` query endpoint (filterable by entity type, actor, date range)
- `AuditTimeline.vue` component (reusable, embedded on process and task detail pages)

**Addresses:** Audit Logging (all table stakes), audit trail infrastructure for subsequent impersonation feature

**Avoids:**
- Same-transaction write pitfall — `async` routing set in `messenger.yaml` before first handler is written
- Actor null pitfall — all domain events verified to carry `actorId` and `actorEmail` as explicit fields
- Write bottleneck pitfall — BRIN index and monthly partitioning required in initial migration (cannot be added later without table rebuild)

**Research flag:** Standard patterns — domain event consumer pattern is already established in the codebase. No additional research needed.

---

### Phase 3: Notification System Enhancement

**Rationale:** The Notification module exists but is missing Mercure push, email delivery, and several event handler types. After Phase 2 (audit log established), notifications can reference audit context. This phase introduces email via Symfony Mailer. `NotificationPreference` entity must be built together with email delivery — it is not a "nice to have" but the only thing preventing an email flood (the critical anti-pattern documented in PITFALLS.md).

**Delivers:**
- `NotificationMercurePublisher` publishing exclusively to `/users/{userId}/notifications` (per-user topic)
- Mercure subscriber JWT scoped to exact topics (`/organizations/{orgId}/activity` + `/users/{userId}/notifications`) — no wildcard
- `EmailNotificationPort` interface + `SymfonyEmailNotificationSender` implementation
- `NotificationPreference` entity (per-user, per-event-type opt-in/out); email is opt-in by default
- `SendEmailMessage` routing to `async` transport in `messenger.yaml`
- New event handlers: `OnProcessCompleted` (notify initiator), `OnProcessCancelled`, `OnTimerFired` (notify next assignee)
- Updated `NotificationBell.vue` with SSE subscription to per-user Mercure topic
- `NotificationPreferences.vue` panel on user profile page (links to `NotificationPreference` entity)

**Addresses:** Notification system (all table stakes + notification preferences differentiator)

**Avoids:**
- Notification flood pitfall — `NotificationPreference` entity and email opt-in by default built together with email delivery
- Mercure topic leak pitfall — per-user topics enforced from first publisher implementation

**Research flag:** Standard patterns — Mercure SSE, Symfony Mailer async routing, and notification preference entity patterns are well-documented and aligned with existing codebase patterns.

---

### Phase 4: Dashboard

**Rationale:** Dashboard depends on both the `audit_log` table (for activity feed, Phase 2) and notification unread count (Phase 3). Building it fourth ensures all data sources exist. The DBAL-direct query approach avoids the cross-module ORM coupling anti-pattern and keeps the Dashboard module architecturally clean.

**Delivers:**
- New `Dashboard` module — query-only, no domain entities, no module repositories
- `GetDashboardSummaryHandler` with raw DBAL queries: my tasks count, overdue tasks count, active processes count, unread notifications count
- `GetActivityFeedHandler` reading `audit_log` table for recent activity (last 20 entries scoped to user's objects)
- `GET /api/v1/organizations/{orgId}/dashboard` endpoint
- `DashboardPage.vue` with PrimeVue Card widgets, Chart.js bar chart (task completion trend), doughnut chart (processes by status)
- `useDashboardStore` (Pinia) with 60-second auto-refresh
- Redis cache on dashboard summary (30-second TTL, keyed by `organization_id`) — Redis already wired in `services.yaml`

**Addresses:** Dashboard (all table stakes + task completion trend chart differentiator)

**Avoids:**
- Cross-module ORM coupling — `DashboardQueryHandler` injects only `Connection`; verified by architecture review during implementation
- Missing `organization_id` filter — every DBAL query scoped; verified by test asserting no cross-org data leakage

**Research flag:** Standard patterns — `ProcessInstanceProjection` in the existing codebase already demonstrates the DBAL read model pattern. `chart.js` integration via PrimeVue `<Chart>` is documented in PrimeVue 4 official docs.

---

### Phase 5: Timer Node Execution

**Rationale:** Timer execution infrastructure exists in the Workflow module (`RabbitMqTimerService`, `OnTimerScheduled`, `FireTimerHandler`) but has two critical gaps: no persistent fallback table (messages lost on RabbitMQ restart) and unverified ISO duration parsing in WorkflowEngine. This phase closes those gaps with targeted verification, the persistent `workflow_scheduled_timers` table, and integration test coverage. It can run in parallel with Phases 3-4 if schedule allows.

**Delivers:**
- `workflow_scheduled_timers` migration: `(id, process_instance_id, token_id, node_id, fire_at, status, scheduled_at, fired_at nullable)`
- `OnTimerScheduled` updated to INSERT into persistent table AND dispatch `DelayStamp` message (message is accelerator, table is source of truth)
- `FireTimerHandler` updated to mark timer row as `fired`; idempotency guard on duplicate firings
- `app:workflow:fire-overdue-timers` console command for cron fallback (queries `fire_at <= NOW() AND status = pending`)
- `symfony/scheduler` integration polling overdue timers every minute
- ISO 8601 duration parsing verification in `WorkflowEngine` (`\DateInterval::createFromDateString()`)
- Integration tests: duration timer fires, date timer fires, timer cancelled with process, timer survives RabbitMQ restart (fallback fires within 5 minutes)
- Designer timer config serialization verification

**Addresses:** Timer Node Execution (all table stakes: duration, date, variable expressions, cancellation)

**Avoids:**
- RabbitMQ DelayStamp reliability pitfall — persistent table is source of truth from day one; fallback command tested with RabbitMQ restart scenario

**Research flag:** Verify RabbitMQ Docker image plugin status before starting. If `rabbitmq-delayed-message-exchange` is currently enabled in `docker-compose.yml`, plan for configuration migration to DLX/TTL built-in mechanism (plugin archived, cannot be used going forward).

---

### Phase 6: Super Admin Impersonation

**Rationale:** Impersonation requires the AuditLog infrastructure from Phase 2 (impersonation events must be logged with `impersonated_by` in every audit entry during the session). Building it last among the core features ensures the audit trail is production-ready before enabling this sensitive capability.

**Delivers:**
- `POST /api/v1/admin/impersonate/{userId}` endpoint — ROLE_SUPER_ADMIN only; returns short-lived impersonation JWT with `impersonated_by` claim
- `ImpersonationVoter` preventing impersonating another super admin (privilege escalation guard)
- Pre-impersonation reason modal; reason logged in `impersonation.started` audit entry
- `impersonation.started` and `impersonation.ended` audit log entries (both actor IDs present)
- All audit entries written during impersonation carry `actor_id` (impersonated user) and `impersonated_by` (admin)
- Persistent orange banner in `AppTopbar.vue` when impersonation JWT is active
- Frontend stores impersonation JWT separately; exit button discards it and restores original admin JWT

**Addresses:** Super Admin Impersonation (all table stakes + impersonation reason differentiator)

**Avoids:**
- JWT/stateless incompatibility pitfall — custom JWT endpoint, NOT `switch_user: true` in `security.yaml`
- Missing audit trail — reason field and both actor IDs in every audit entry
- Privilege escalation — voter blocks impersonating other super admins; endpoint restricted to ROLE_SUPER_ADMIN

**Research flag:** Standard patterns — custom JWT impersonation approach fully researched. `JWTManager::create()` API from `lexik/jwt-authentication-bundle` confirmed to support custom payload claims.

---

### Phase Ordering Rationale

- **Profile before everything:** Avatar URL must be on `User` entity before it can appear in AuditLog actor display and Dashboard task cards; CI/CD provides regression safety for all subsequent work.
- **Audit before Dashboard and Impersonation:** Dashboard activity feed reads `audit_log`; impersonation events require an established audit trail.
- **Notifications before Dashboard:** Dashboard unread count widget reads from the Notification module's data.
- **Timer can run in parallel:** No dependency on Audit, Notifications, or Dashboard. Can be done alongside Phases 3-4 as a separate work stream.
- **Impersonation last:** Requires Audit Logging to be complete; is the most security-sensitive feature and benefits from established audit patterns.

### Research Flags

Phases likely needing targeted investigation during planning:
- **Phase 5 (Timer):** Verify Docker RabbitMQ image plugin status before starting (`grep -r "delayed" docker-compose.yml`). If plugin is currently enabled, plan for configuration migration to DLX/TTL built-in approach.

Phases with well-established patterns (no additional research needed):
- **Phase 1 (Profile + CI/CD):** S3 presigned URL and GitHub Actions patterns are fully documented and already used in the codebase.
- **Phase 2 (Audit Logging):** Domain event consumer pattern already established in `Notification/Application/EventHandler/`.
- **Phase 3 (Notifications):** Mercure, Mailer, and preference entity patterns are standard; existing `NotificationBell.vue` provides the starting point.
- **Phase 4 (Dashboard):** DBAL read model pattern already demonstrated by `ProcessInstanceProjection`.
- **Phase 6 (Impersonation):** Custom JWT approach fully researched; LexikJWT `create()` API confirmed.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Verified against official Symfony 8 docs, PrimeVue 4 docs, AWS SDK PHP v3 docs, packagist; codebase `composer.json` and `package.json` confirmed; RabbitMQ plugin archive status verified on GitHub |
| Features | HIGH | Core requirements verified against Camunda 8, Flowable, IBM BPM, Oracle BPM official docs; CI/CD stage ordering from Symfony community sources (MEDIUM for some CI ordering rationale) |
| Architecture | HIGH | Based on direct codebase analysis of all 9 modules, `messenger.yaml`, `security.yaml`, `DispatchDomainEventsMiddleware`, `ProcessInstanceProjection`, and `RabbitMqTimerService`; Symfony official docs for all patterns |
| Pitfalls | HIGH | All critical pitfalls grounded in existing codebase code paths and official docs; RabbitMQ reliability confirmed via CloudAMQP blog and GitHub archive notice; JWT impersonation incompatibility confirmed in LexikJWT issues #652/#1196 |

**Overall confidence: HIGH**

### Gaps to Address

- **RabbitMQ plugin status in docker-compose.yml:** STACK.md notes the `rabbitmq-delayed-message-exchange` plugin is archived but does not confirm whether the current Docker setup has it enabled. Before Phase 5 planning: check `docker-compose.yml` for plugin references and plan migration if needed.
- **Notification module persistent storage:** ARCHITECTURE.md notes the existing Notification module saves to DB, but this needs verification during Phase 3 planning — specifically whether `Notification` entity has an active migration and persistent repository, or if it relies on Mercure-only delivery.
- **Timer node designer config serialization:** ARCHITECTURE.md notes the designer timer config serialization "likely already exists" but needs verification that the UI correctly serializes `fire_at` values before Phase 5 implementation begins.
- **`composer audit` baseline:** CI pipeline recommends a `composer audit` step but current dependency CVE status is unknown. Run `composer audit` during Phase 1 CI setup to establish the baseline.

---

## Sources

### Primary (HIGH confidence)

- Symfony 8 Scheduler docs — `symfony.com/doc/current/scheduler.html` — timer node date-based approach, `RecurringMessage` API
- Symfony 8 Messenger docs — `symfony.com/doc/current/messenger.html` — `DelayStamp`, AMQP DLX/TTL, async routing
- Symfony Security — Impersonating a User — `symfony.com/doc/current/security/impersonating_user.html` — confirmed JWT stateless incompatibility
- PrimeVue 4 Chart docs — `primevue.org/chart/` — confirmed Chart.js peer dependency required separately
- AWS SDK PHP v3 presigned URL docs — `docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3-presigned-url.html` — `createPresignedRequest` API
- `rabbitmq/rabbitmq-delayed-message-exchange` GitHub — archived January 29, 2026, Mnesia-dependent
- Camunda 8 Timer Events — `docs.camunda.io/docs/components/modeler/bpmn/timer-events/` — timer feature baseline
- Procivo codebase direct analysis — all 9 modules, `messenger.yaml`, `security.yaml`, `RabbitMqTimerService.php`, `ProcessInstanceProjection.php`, `TaskMercurePublisher.php`, `S3FileStorage.php`, `composer.json`, `package.json`

### Secondary (MEDIUM confidence)

- CloudAMQP — "Pitfalls of the Delayed Message Exchange" — Mnesia reliability, memory exhaustion, node failure data loss
- LexikJWTAuthenticationBundle issues #652 and #1196 — JWT + `switch_user` failure mode confirmation
- OpsHub Signal — Audit Trail Best Practices — audit entry schema fields and compliance requirements
- `shivammathur/setup-php@v2` GitHub Marketplace — de-facto standard for Symfony CI in 2025-2026
- KissFlow BPM Platform features 2026 — dashboard metrics baseline
- Authress Knowledge Base — impersonation chaining risk and privilege escalation patterns
- Medium — "Production-Ready Audit Logs in PostgreSQL" — BRIN index, monthly partitioning strategy for append-only tables
- Mercure spec — `mercure.rocks/spec` — subscriber JWT topic selectors for per-user security

### Tertiary (supporting reference)

- Oracle BPM notifications docs — notification preference patterns
- Vegam BPM Metrics and KPIs — cycle time chart rationale
- IBM BPM Process Portal — dashboard baseline feature comparison
- Detectify Labs — S3 upload policy bypass attack vectors (avatar MIME type validation rationale)

---
*Research completed: 2026-03-01*
*Ready for roadmap: yes*
