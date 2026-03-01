# Architecture Research

**Domain:** BPM Platform — v2.0 Production-Ready Features (Procivo)
**Researched:** 2026-03-01
**Confidence:** HIGH — based on direct codebase analysis of all 9 modules + verified against Symfony official docs

---

## Context: What Already Exists

The codebase is a well-structured Modular Monolith with Clean Architecture, DDD, CQRS. Before documenting
new integration points, the existing module structure must be understood.

### Current Module Inventory

| Module | Role | Key Entities | Domain Events |
|--------|------|-------------|---------------|
| Shared | Cross-cutting kernel | AggregateRoot, DomainEvent, buses | — |
| Identity | User auth + JWT | User | UserRegistered, UserActivated, PasswordChanged |
| Organization | Org structure + RBAC | Organization, Department, Employee, Role | SeedDefaultRoles, AssignDefaultRole |
| TaskManager | Tasks + boards + kanban | Task, Board, Comment, TaskAttachment | TaskAssigned, TaskStatusChanged, CommentAdded, TaskClaimed |
| Workflow | BPMN engine + definitions | ProcessDefinition, ProcessInstance, Token, WorkflowTaskLink | ProcessStarted, ProcessCompleted, TimerScheduled, TaskNodeActivated, ... |
| Notification | In-app notifications | Notification | — (consumer only) |
| Resource | Resource management | (present, lower use) | — |
| Directory | Dynamic definitions | (present, lower use) | — |
| Search | Elasticsearch | (stub) | — |

### Current Cross-Module Communication Patterns

**Pattern 1 — Domain Events via event.bus (primary)**
Domain events are raised inside `AggregateRoot.recordEvent()`, pulled by `DispatchDomainEventsMiddleware`
after the command handler commits, then dispatched to `event.bus`. Other modules subscribe via
`#[AsMessageHandler(bus: 'event.bus')]`. Example: `Notification::OnTaskAssigned` listens to
`TaskManager::TaskAssignedEvent`.

**Pattern 2 — Port/Adapter interface (for synchronous cross-module queries)**
When Module A needs data from Module B synchronously (not via events), it defines an interface
in its own `Application/Port/` layer. Module B's Infrastructure provides the implementation
(adapter). Example: `TaskManager::OrganizationQueryPort` → `DoctrineOrganizationQueryAdapter`
reads Organization repositories directly.

**Pattern 3 — Direct repository injection across modules (Infrastructure only)**
Infrastructure adapters in one module inject repositories from another module. Used only when
Port/Adapter would be over-engineering for simple reads. Example: `OnTaskAssigned` injects
`TaskRepositoryInterface` from TaskManager to read the task title.

**Pattern 4 — Async via RabbitMQ (for expensive or non-blocking work)**
Certain events are routed to the `async` transport in `messenger.yaml`. This offloads work from
the HTTP request cycle. Example: `TaskAssignedEvent`, `TaskStatusChangedEvent`, `CommentAddedEvent`
are all routed async. `FireTimerMessage` uses `DelayStamp` for delayed execution.

**Pattern 5 — Read model projections (for query performance)**
`ProcessInstanceProjection` in `Workflow/Infrastructure/ReadModel/` subscribes to Workflow events
and maintains a denormalized `workflow_process_instances_view` table using raw DBAL. Avoids
replaying the event store for every list query.

---

## System Overview

```
┌──────────────────────────────────────────────────────────────────────────┐
│                     Presentation Layer (HTTP Controllers)                 │
│  Identity  │ Organization │ TaskManager │ Workflow │ Notification │ ...   │
│  Controller│  Controller  │  Controller │ Controller│  Controller  │ NEW  │
└────────────┴──────────────┴─────────────┴──────────┴──────────────┴──────┘
             │ CommandBus / QueryBus (synchronous within HTTP request)
┌────────────┴──────────────────────────────────────────────────────────────┐
│                     Application Layer (Handlers, EventHandlers)            │
│  Command Handlers       Query Handlers        Event Handlers               │
│  (write side)           (read side)           (#[AsMessageHandler]         │
│                                                bus: 'event.bus')           │
└────────────┬──────────────────────────────────────────────────────────────┘
             │ DomainEvents pulled by DispatchDomainEventsMiddleware
┌────────────┴──────────────────────────────────────────────────────────────┐
│                     Domain Layer (Entities, Events, Repositories)          │
│  AggregateRoot → recordEvent() → pulled by middleware → event.bus          │
└────────────┬──────────────────────────────────────────────────────────────┘
             │ Doctrine XML mappings / Raw DBAL / S3 / Mercure / RabbitMQ
┌────────────┴──────────────────────────────────────────────────────────────┐
│                     Infrastructure Layer                                    │
│  PostgreSQL │ Redis │ RabbitMQ (async transport) │ Mercure SSE │ S3        │
└────────────────────────────────────────────────────────────────────────────┘
```

---

## New Feature Integration Points

### Feature 1: Audit Logging

**Verdict: New `AuditLog` module, fed exclusively by existing domain events.**

The codebase already emits rich domain events from every significant action. Audit logging
is a pure consumer of these events — it does not need to change any existing module.

**New module location:** `backend/src/AuditLog/`

```
AuditLog/
├── Domain/
│   ├── Entity/
│   │   └── AuditEntry.php           # id, actor_id, actor_name, action, module, entity_id, payload (JSON), occurred_at
│   ├── Repository/
│   │   └── AuditEntryRepositoryInterface.php
│   └── ValueObject/
│       └── AuditAction.php          # enum: created, updated, deleted, transition, process_started, etc.
├── Application/
│   └── EventHandler/
│       ├── OnTaskCreated.php        # listens TaskCreatedEvent → writes AuditEntry
│       ├── OnTaskStatusChanged.php  # listens TaskStatusChangedEvent → writes AuditEntry
│       ├── OnTaskAssigned.php       # listens TaskAssignedEvent → writes AuditEntry
│       ├── OnProcessStarted.php     # listens ProcessStartedEvent → writes AuditEntry
│       ├── OnProcessCompleted.php   # listens ProcessCompletedEvent → writes AuditEntry
│       └── OnCommentAdded.php       # listens CommentAddedEvent → writes AuditEntry
├── Infrastructure/
│   ├── Persistence/Doctrine/Mapping/
│   │   └── AuditEntry.orm.xml
│   └── Repository/
│       └── DoctrineAuditEntryRepository.php
└── Presentation/
    └── Controller/
        └── AuditLogController.php   # GET /api/v1/audit-log?entity_id=X&module=Y
```

**Integration pattern:** All `OnXxx` event handlers follow the same pattern already used in
`Notification/Application/EventHandler/`. Each handler is tagged `#[AsMessageHandler(bus: 'event.bus')]`.

**What goes in `AuditEntry`:**
- `actor_id` (string UUID) — the user who triggered the action
- `actor_name` (string) — denormalized name for historical display (snapshot)
- `action` (enum string) — e.g. `task.assigned`, `process.started`, `task.status_changed`
- `module` (string) — e.g. `TaskManager`, `Workflow`
- `entity_id` (string UUID) — the affected entity (task ID, process instance ID)
- `entity_type` (string) — e.g. `Task`, `ProcessInstance`
- `payload` (JSONB) — relevant snapshot data (old/new status, assignee ID, etc.)
- `occurred_at` (datetime) — from `DomainEvent::occurredAt()`
- `organization_id` (string) — for multi-tenant scoping

**Async routing:** Add audit event handlers to the `async` transport in `messenger.yaml`:
```yaml
App\AuditLog\Application\EventHandler\*: async
```
This prevents audit writes from adding latency to the HTTP request.

**Critical decision:** Do NOT use Doctrine lifecycle listeners (postPersist, postUpdate). These
capture all entity changes including internal state that has no user-visible meaning. Domain events
capture business-meaningful actions — the right granularity for audit logs.

**messenger.yaml additions needed:**
```yaml
routing:
  App\TaskManager\Domain\Event\TaskCreatedEvent: async     # currently missing
  App\TaskManager\Domain\Event\TaskDeletedEvent: async     # currently missing
  App\TaskManager\Domain\Event\CommentAddedEvent: async    # already present
  App\TaskManager\Domain\Event\TaskAssignedEvent: async    # already present
  App\TaskManager\Domain\Event\TaskStatusChangedEvent: async  # already present
  App\Workflow\Domain\Event\ProcessStartedEvent: async    # new routing needed
  App\Workflow\Domain\Event\ProcessCompletedEvent: async  # new routing needed
  App\Workflow\Domain\Event\ProcessCancelledEvent: async  # new routing needed
```

**Frontend:** New `audit-log` module or sub-feature inside tasks/workflow pages showing
a timeline component. Can be a simple query-only view with PrimeVue `Timeline` component.

---

### Feature 2: Notification System (Enhancement)

**Verdict: Notification module already exists — extend it, do not create new.**

The existing `Notification` module has `Notification` entity, 3 event handlers, list/count/mark-read
API, and `NotificationBell.vue` on the frontend. What is missing for v2.0:

**Missing backend capabilities:**
1. Email delivery — add `EmailNotificationPort` interface in `Notification/Application/Port/`
   and implement via Symfony Mailer in `Notification/Infrastructure/Mailer/`
2. User preferences — add `NotificationPreference` entity (user_id, type, channel: in_app|email|both)
3. Mercure real-time push — currently Notification saves to DB but does NOT push via Mercure

**Missing event handlers (new `NotificationType` values needed):**
- `OnProcessStarted` → notify process initiator
- `OnProcessCompleted` → notify initiator + participants
- `OnTaskClaimed` → notify task creator

**Mercure integration for Notification:**
Currently `TaskMercurePublisher` in TaskManager pushes to `/organizations/{id}/tasks`. Add
`NotificationMercurePublisher` in `Notification/Infrastructure/Mercure/` that pushes to the
user-specific topic `/users/{recipientId}/notifications`. The `Notification::create()` call
already happens in `OnTaskAssigned` — add a Mercure publish call right after the DB save.

```
Notification/Infrastructure/Mercure/
└── NotificationMercurePublisher.php   # publishes to /users/{userId}/notifications
```

**Frontend additions:**
- `NotificationBell.vue` already exists — add SSE subscription via EventSource to
  `/api/.well-known/mercure?topic=/users/{userId}/notifications`
- Add `NotificationPreferences.vue` panel in user profile section
- Add email notification toggle per notification type

**New `NotificationType` enum values:**
```php
case ProcessStarted = 'process_started';
case ProcessCompleted = 'process_completed';
case TaskClaimed = 'task_claimed';
case TimerFired = 'timer_fired';
```

---

### Feature 3: Dashboard

**Verdict: New `Dashboard` module — query-only, cross-module read via DBAL adapters.**

Dashboard requires data from multiple modules: tasks (assigned to me, overdue), process instances
(active, by definition), notifications (unread count). This is pure read — no writes.

**Approach: Dedicated Dashboard query handler using raw DBAL**

Do NOT inject repositories from multiple modules into a single handler. Instead, create a
dedicated `Dashboard` module with query handlers that access the underlying tables directly via
DBAL (same approach as `ProcessInstanceProjection`).

```
Dashboard/
├── Application/
│   └── Query/
│       ├── GetDashboardSummary/
│       │   ├── GetDashboardSummaryQuery.php    # { userId, organizationId }
│       │   └── GetDashboardSummaryHandler.php  # DBAL multi-table query
│       └── GetActivityFeed/
│           ├── GetActivityFeedQuery.php         # { organizationId, limit }
│           └── GetActivityFeedHandler.php       # reads audit_log table
└── Presentation/
    └── Controller/
        └── DashboardController.php              # GET /api/v1/organizations/{id}/dashboard
```

**`GetDashboardSummaryHandler` data sources (via DBAL, not repositories):**
- `task_manager_tasks` — count assigned-to-me tasks by status
- `task_manager_tasks` — count overdue tasks (due_date < NOW() AND status != done)
- `workflow_process_instances_view` — count active processes started by me or my org
- `notification` table — unread count (could also come from `Notification` module's query handler)

**Why DBAL over injecting repositories:** Injecting repositories from 3 modules into one handler
violates bounded context isolation. DBAL reads denormalized tables directly — the same approach
already used by `ProcessInstanceProjection`. For a dashboard, raw SQL is appropriate and fast.

**Alternative rejected:** A `DashboardQueryPort` in each source module that Dashboard implements.
This adds 3 cross-module port interfaces for essentially a thin wrapper around SELECT COUNT(*).
Overkill for a dashboard at this scale.

**Frontend:** New `dashboard` module with:
- `/organizations/:orgId/dashboard` route
- `DashboardPage.vue` with PrimeVue `Card`, `Chart` (for process counts), `DataTable` (for tasks)
- `useDashboardStore` (Pinia) for fetch + cache

---

### Feature 4: User Profile + Avatar

**Verdict: Extend `Identity` module — add `avatarUrl` field to `User` entity and S3 upload port.**

User profile (avatar, display name) belongs to the Identity bounded context. The `User` entity
already has `firstName`, `lastName`, `email`. Adding avatar URL is an extension of the same entity.

**Backend changes to Identity module:**

1. Add `avatarUrl` field to `User` entity (nullable string, stored in `identity_users.avatar_url`)
2. Add `UserAvatar.orm.xml` field to `User.orm.xml` — single `<field name="avatarUrl" ...>`
3. New command: `UpdateUserProfile` → `UpdateUserProfileHandler` (updates firstName, lastName, avatarUrl)
4. New command: `UploadUserAvatar` → `UploadUserAvatarHandler` (uploads to S3, saves URL to User)
5. New port: `Identity/Application/Port/FileStorageInterface.php` (mirrors TaskManager's pattern)
6. Infrastructure: `Identity/Infrastructure/Storage/S3UserAvatarStorage.php`

```
Identity/Application/Command/
├── UpdateUserProfile/
│   ├── UpdateUserProfileCommand.php   # { userId, firstName, lastName, bio? }
│   └── UpdateUserProfileHandler.php
└── UploadUserAvatar/
    ├── UploadUserAvatarCommand.php    # { userId, fileContent, mimeType }
    └── UploadUserAvatarHandler.php    # calls FileStorageInterface → saves avatarUrl to User

Identity/Application/Port/
└── FileStorageInterface.php           # store(content, mimeType): string (returns URL)

Identity/Infrastructure/Storage/
└── S3UserAvatarStorage.php            # uses same AWS SDK already in composer.json
```

**S3 key pattern:** `avatars/{userId}.{ext}` — simple, deterministic, easy to invalidate.

**Frontend:**
- Extend `useAuthStore` to include `avatarUrl` field from `GetCurrentUser` API response
- Add `UserAvatar.vue` shared component — shows avatar or initials fallback
- Add `ProfilePage.vue` in `modules/auth/pages/` with `FileUpload` (PrimeVue) for avatar
- Update `AppTopbar.vue` to show avatar + name

**UserDTO extension:**
```php
public string $avatarUrl;  // nullable string → empty string if null
```

**Cross-module concern:** Other modules that display user names/avatars (TaskManager, Workflow) should
get avatarUrl from the JWT claims or from a shared query. Recommendation: add `avatarUrl` to JWT
payload via `JwtCreatedListener` (already exists in Identity/Infrastructure/Security/).

---

### Feature 5: Timer Node Execution

**Verdict: Already partially built in Workflow module — complete the execution gap.**

The timer infrastructure is almost complete. What exists:
- `TimerServiceInterface` + `RabbitMqTimerService` — schedules `FireTimerMessage` with `DelayStamp`
- `OnTimerScheduled` event handler — calls `timerService.scheduleTimer()`
- `FireTimerHandler` — receives `FireTimerMessage`, calls `instance.fireTimer()` + `engine.resumeToken()`
- `ProcessInstanceProjection` — handles `TimerScheduledEvent` and `TimerFiredEvent` to update view

**What is missing for full timer execution:**

1. **Timer node config parsing** — `TimerScheduledEvent` fires but the `fireAt` datetime must be
   derived from the timer node's config (ISO duration like `PT1H`, or a specific datetime). The
   `OnTimerScheduled` event handler does `new \DateTimeImmutable($event->fireAt)` which means the
   event already carries the calculated `fireAt`. Need to verify that the WorkflowEngine correctly
   calculates `fireAt` from the node config when it activates a Timer node.

2. **Duration parsing** — ISO 8601 duration (`PT1H`, `P1D`) → `\DateInterval` → add to `new \DateTimeImmutable()`.
   PHP's `\DateInterval::createFromDateString()` handles this. No extra library needed.

3. **Test coverage** — `FireTimerHandler` needs integration tests verifying the token resumes correctly
   after timer fires.

4. **Designer config** — Timer node property panel needs duration/datetime picker. Likely this
   already exists in the designer (since `TimerScheduledEvent` carries a `fireAt` value), but needs
   verification that the UI correctly serializes the value.

**RabbitMQ `DelayStamp` concern:** The existing implementation uses Symfony's `DelayStamp` which
relies on the `x-delay` header (requires `rabbitmq_delayed_message_exchange` plugin) or falls back
to dead-letter exchange TTL. Verify the Docker RabbitMQ image has the delayed message exchange
plugin enabled. If not, the fallback (DLE + TTL) works but has a 1-second granularity floor.

```yaml
# docker-compose.yml — RabbitMQ should use:
image: rabbitmq:4-management
# AND either:
# - rabbitmq-delayed-message-exchange plugin (requires community image)
# - OR: accept DLE TTL-based delays (simpler, sufficient for HR/process timers)
```

**No new files needed in Workflow module** — architecture is correct. Work is:
- Verify node config → `fireAt` computation in WorkflowEngine
- Add tests for timer flow (TimerScheduled → FireTimer → token resumes)
- Possibly fix designer timer config serialization

---

### Feature 6: CI/CD Pipeline

**Verdict: New `.github/workflows/` directory — no module changes needed.**

CI/CD is infrastructure-only. The Makefile already defines all quality targets (`test`, `lint`, `fix`, `stan`).

**Recommended pipeline structure:**

```
.github/
└── workflows/
    ├── ci.yml          # runs on every PR: lint, stan, test
    └── cd.yml          # runs on merge to main: build Docker image (optional for pet project)
```

**`ci.yml` steps:**
```yaml
name: CI
on: [push, pull_request]

jobs:
  backend:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:18
        env: { POSTGRES_DB: procivo_test, POSTGRES_USER: app, POSTGRES_PASSWORD: secret }
        ports: ['5432:5432']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.4', extensions: 'amqp, redis, pdo_pgsql' }
      - run: cd backend && composer install --no-interaction
      - run: cd backend && vendor/bin/php-cs-fixer fix --dry-run --diff
      - run: cd backend && vendor/bin/phpstan analyse
      - run: cd backend && vendor/bin/phpunit

  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '24' }
      - run: cd frontend && npm ci
      - run: cd frontend && npm run type-check
      - run: cd frontend && npm run lint
      - run: cd frontend && npm run test:unit
```

**Pre-commit hooks:** Add `.pre-commit-config.yaml` or use a simple `Makefile` hook. For pet
project pace, GitHub Actions CI is sufficient — local pre-commit hooks are optional.

---

### Feature 7: Super Admin Impersonation

**Verdict: Pure Security layer concern — extend `Identity` module's security config only.**

Symfony has native `switch_user` impersonation built into the security firewall. No new module needed.

**Implementation:**

1. Add `ROLE_SUPER_ADMIN` to `User.roles` JSON field
2. Configure `switch_user` in `security.yaml`:
   ```yaml
   security:
     firewalls:
       main:
         switch_user: { role: CAN_SWITCH_USER }
   ```
3. Create `ImpersonationVoter` in `Identity/Infrastructure/Security/`:
   ```php
   class ImpersonationVoter extends Voter
   {
       protected function supports(string $attribute, mixed $subject): bool
       {
           return 'CAN_SWITCH_USER' === $attribute;
       }
       protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
       {
           return in_array('ROLE_SUPER_ADMIN', $token->getRoleNames(), true);
       }
   }
   ```
4. Add `IS_IMPERSONATOR` attribute check to relevant controllers for audit trail
5. Add `UserImpersonatedEvent` (domain event) to record in AuditLog

**Frontend:** Add impersonation notice banner in `AppTopbar.vue` when `IS_IMPERSONATOR` is true
(detectable via a flag in the JWT or a `/me` API response field).

---

## Component Boundaries Summary

| New/Modified | Module | Action | Communicates With |
|-------------|--------|--------|-------------------|
| AuditLog | NEW | Consumes domain events, writes AuditEntry | Listens to: Identity, TaskManager, Workflow events |
| Notification | EXTEND | Add email port, Mercure push, new event handlers | Listens to: TaskManager, Workflow; Publishes: Mercure |
| Dashboard | NEW | Read-only DBAL queries across modules | Reads: task_manager_*, workflow_*, audit_log, notification tables |
| Identity | EXTEND | Add avatarUrl to User, S3 upload port, profile commands | Publishes: UserProfileUpdated event |
| Workflow | COMPLETE | Timer node execution already structured | No new cross-module deps |
| CI/CD | INFRA | GitHub Actions workflow files | None |
| Identity | EXTEND | Impersonation voter + ROLE_SUPER_ADMIN | AuditLog (via event) |

---

## Data Flow

### Audit Log Flow

```
HTTP Request (e.g. CompleteTask)
  → CommandBus → ExecuteTaskActionHandler
    → ProcessInstance.executeAction() → recordEvent(ProcessCompletedEvent)
    → DispatchDomainEventsMiddleware pulls events → EventBus.dispatch(ProcessCompletedEvent)
      → [async transport → RabbitMQ]
        → Consumer → AuditLog::OnProcessCompleted → AuditEntry saved to DB
        → AuditLog::... → multiple handlers for same event (allowed by event.bus config)
```

### Notification + Real-Time Flow

```
TaskAssignedEvent dispatched to event.bus (async)
  → Notification::OnTaskAssigned
    → Notification::create() → repository.save()
    → NotificationMercurePublisher.publish('/users/{recipientId}/notifications', payload)
      → Frontend EventSource receives push → NotificationBell badge increments
```

### Dashboard Flow

```
Frontend: GET /api/v1/organizations/{orgId}/dashboard
  → DashboardController → QueryBus → GetDashboardSummaryHandler
    → DBAL: SELECT COUNT(*) FROM task_manager_tasks WHERE assignee_id = ? AND status != 'done'
    → DBAL: SELECT COUNT(*) FROM workflow_process_instances_view WHERE organization_id = ? AND status = 'running'
    → DBAL: SELECT COUNT(*) FROM notification WHERE recipient_id = ? AND is_read = false
    → Returns aggregated DTO
```

### Timer Execution Flow

```
WorkflowEngine activates Timer node
  → ProcessInstance.recordEvent(TimerScheduledEvent { fireAt })
  → event.bus → Workflow::OnTimerScheduled
    → RabbitMqTimerService.scheduleTimer() → dispatch FireTimerMessage with DelayStamp
      → [RabbitMQ holds message until TTL expires]
        → Consumer → FireTimerHandler
          → instance.fireTimer() → engine.resumeToken()
          → Saves instance → DispatchDomainEventsMiddleware → TimerFiredEvent dispatched
            → ProcessInstanceProjection updates view (token status = active)
```

---

## Recommended Project Structure (new additions only)

```
backend/src/
├── AuditLog/                                   # NEW module
│   ├── Domain/
│   │   ├── Entity/AuditEntry.php
│   │   ├── Repository/AuditEntryRepositoryInterface.php
│   │   └── ValueObject/AuditAction.php
│   ├── Application/
│   │   └── EventHandler/
│   │       ├── OnTaskCreated.php
│   │       ├── OnTaskStatusChanged.php
│   │       ├── OnTaskAssigned.php
│   │       ├── OnProcessStarted.php
│   │       ├── OnProcessCompleted.php
│   │       └── OnCommentAdded.php
│   ├── Infrastructure/
│   │   ├── Persistence/Doctrine/Mapping/AuditEntry.orm.xml
│   │   └── Repository/DoctrineAuditEntryRepository.php
│   └── Presentation/
│       └── Controller/AuditLogController.php
│
├── Dashboard/                                  # NEW module (query-only)
│   ├── Application/
│   │   └── Query/
│   │       ├── GetDashboardSummary/
│   │       │   ├── GetDashboardSummaryQuery.php
│   │       │   └── GetDashboardSummaryHandler.php  # DBAL
│   │       └── GetActivityFeed/
│   │           ├── GetActivityFeedQuery.php
│   │           └── GetActivityFeedHandler.php      # reads audit_log
│   └── Presentation/
│       └── Controller/DashboardController.php
│
├── Identity/                                   # EXTENDED
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── UpdateUserProfile/             # new
│   │   │   └── UploadUserAvatar/              # new
│   │   └── Port/
│   │       └── FileStorageInterface.php       # new (mirrors TaskManager pattern)
│   └── Infrastructure/
│       └── Storage/
│           └── S3UserAvatarStorage.php        # new
│
├── Notification/                              # EXTENDED (email + Mercure push)
│   ├── Application/
│   │   ├── EventHandler/
│   │   │   ├── OnProcessStarted.php           # new
│   │   │   └── OnProcessCompleted.php         # new
│   │   └── Port/
│   │       └── EmailNotificationPort.php      # new (interface)
│   └── Infrastructure/
│       ├── Mailer/
│       │   └── SymfonyEmailNotificationSender.php  # new
│       └── Mercure/
│           └── NotificationMercurePublisher.php    # new
│
└── Workflow/                                   # NO NEW FILES — complete existing timer logic
    └── (verify TimerNode config → fireAt calculation in WorkflowEngine)

frontend/src/modules/
├── dashboard/                                  # NEW module
│   ├── api/dashboard.api.ts
│   ├── pages/DashboardPage.vue
│   ├── stores/dashboard.store.ts
│   └── types/dashboard.types.ts
│
├── audit-log/                                  # NEW module (or sub-page of tasks/workflow)
│   ├── api/audit-log.api.ts
│   └── components/AuditTimeline.vue
│
├── auth/                                       # EXTENDED
│   └── pages/
│       └── ProfilePage.vue                     # new (avatar upload + name edit)
│
└── notifications/                              # EXTENDED
    └── components/
        └── NotificationPreferences.vue         # new
```

---

## Architectural Patterns

### Pattern 1: Event Handler as Cross-Module Bridge

**What:** An event handler in Module B subscribes to a domain event from Module A. The handler
lives in Module B's `Application/EventHandler/`. It may inject Module A's repositories for
additional data lookups (acceptable), but must NOT call Module A's commands.

**When to use:** Any side effect in Module B triggered by something happening in Module A.
Notification on task assignment, AuditLog on process start, etc.

**Example (new OnProcessCompleted in Notification module):**
```php
#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnProcessCompleted
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private NotificationMercurePublisher $mercurePublisher,
    ) {}

    public function __invoke(ProcessCompletedEvent $event): void
    {
        $notification = Notification::create(
            NotificationId::generate(),
            $event->startedBy,
            NotificationType::ProcessCompleted,
            'Process completed',
            sprintf('Process "%s" has completed.', $event->processDefinitionId),
            $event->processInstanceId,
        );
        $this->notificationRepository->save($notification);
        $this->mercurePublisher->publishToUser($event->startedBy, $notification);
    }
}
```

**Trade-offs:** Handler count grows with number of cross-module concerns. Keep handlers focused —
one handler per concern per event (AuditLog's `OnProcessCompleted` is separate from Notification's).

### Pattern 2: Port/Adapter for Synchronous Cross-Module Data

**What:** Module A defines an interface in `Application/Port/`. Module B's Infrastructure
implements it. Symfony DI wires the alias.

**When to use:** When Module A needs to call Module B synchronously during a command, and the
data needed is not available in Module A's domain.

**Example (new UserQueryPort for Dashboard or AuditLog):**
```php
// AuditLog/Application/Port/UserQueryPort.php
interface UserQueryPort
{
    public function getUserDisplayName(string $userId): string;  // returns "John Doe" or "Unknown"
}

// AuditLog/Infrastructure/Identity/DoctrineUserQueryAdapter.php
final readonly class DoctrineUserQueryAdapter implements UserQueryPort
{
    public function __construct(private Connection $connection) {}

    public function getUserDisplayName(string $userId): string
    {
        $row = $this->connection->fetchAssociative(
            'SELECT first_name, last_name FROM identity_users WHERE id = ?',
            [$userId]
        );
        return $row ? trim($row['first_name'] . ' ' . $row['last_name']) : 'Unknown';
    }
}
```

**Trade-offs:** Clean boundary — Module A has no compile-time dependency on Module B. Requires
a new interface + adapter pair per cross-module need. For Dashboard, where reads are from many
modules, prefer raw DBAL in a dedicated query handler instead.

### Pattern 3: DBAL Read Model for Cross-Module Queries

**What:** Query handlers read directly from DB tables (raw SQL / DBAL) without going through
domain repositories or entity managers. Used by `ProcessInstanceProjection` and recommended for
`GetDashboardSummaryHandler`.

**When to use:** Dashboard-style aggregations that span multiple modules. Read-only. Performance-critical.

**Example (Dashboard handler):**
```php
final readonly class GetDashboardSummaryHandler
{
    public function __construct(private Connection $connection) {}

    public function __invoke(GetDashboardSummaryQuery $query): DashboardSummaryDTO
    {
        $myTaskCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM task_manager_tasks t
             JOIN task_manager_task_assignments ta ON ta.task_id = t.id
             WHERE ta.assignee_id = ? AND t.status NOT IN (\'done\', \'cancelled\')',
            [$query->userId]
        );

        $activeProcesses = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM workflow_process_instances_view
             WHERE organization_id = ? AND status = \'running\'',
            [$query->organizationId]
        );

        return new DashboardSummaryDTO(myTaskCount: $myTaskCount, activeProcesses: $activeProcesses);
    }
}
```

**Trade-offs:** Tight coupling to table names (they change with migrations). Document table names
as contracts between modules. Fast — no ORM overhead.

---

## Anti-Patterns

### Anti-Pattern 1: Doctrine Lifecycle Listeners for Audit Logging

**What people do:** Add a Doctrine `postPersist`/`postUpdate` listener that writes to audit_log
for every entity change.

**Why it's wrong:** Captures low-level ORM noise (internal state changes, intermediate states
during command execution). No actor information available inside a Doctrine listener. Creates a
dependency on the ORM cycle rather than the business event. Hard to filter meaningful actions.

**Do this instead:** Use domain events (`recordEvent()` in AggregateRoot). Domain events carry
intent and actor context. The `DispatchDomainEventsMiddleware` already handles dispatch.

### Anti-Pattern 2: Dashboard Handler Injecting Multiple Module Repositories

**What people do:** Inject `TaskRepositoryInterface`, `ProcessInstanceRepositoryInterface`,
and `NotificationRepositoryInterface` into a single `GetDashboardSummaryHandler`.

**Why it's wrong:** Breaks bounded context isolation — the handler depends on 3 modules' domain
layers. Repository interfaces are defined in Domain layers, which should not cross module boundaries.
Makes the handler a god object that knows about all modules.

**Do this instead:** Use raw DBAL queries in a dedicated `Dashboard` module handler. Access the
underlying tables directly. This is the same pattern used by `ProcessInstanceProjection`.

### Anti-Pattern 3: Storing Avatar on Filesystem Instead of S3

**What people do:** Save avatar files to the local filesystem (`/var/www/uploads/`).

**Why it's wrong:** The project already uses S3 for TaskManager attachments. Docker containers
are ephemeral — local filesystem writes are lost on restart. S3 is the established pattern.

**Do this instead:** Reuse the S3 pattern already established by `TaskManager/Infrastructure/Storage/S3FileStorage.php`.
Create an identical `S3UserAvatarStorage.php` in Identity/Infrastructure/Storage/.

### Anti-Pattern 4: Storing Notification Preferences in a Separate Micro-Service

**What people do:** Over-engineer notification preferences into a separate microservice.

**Why it's wrong:** The project is a Modular Monolith moving toward microservices later. Preferences
are a simple entity in the Notification module — one table, one entity, one repository.

**Do this instead:** Add `NotificationPreference` entity to the Notification module. Simple CRUD.
Export as a migration. No separate service.

### Anti-Pattern 5: Using Symfony Messenger's `async` transport without explicit routing

**What people do:** Route all domain events to `async` globally.

**Why it's wrong:** Some events need synchronous handling within the same HTTP request (e.g., seeding
default roles on org creation in `SeedDefaultRolesOnOrganizationCreated`). Global async routing
would break these.

**Do this instead:** Route only side-effect events (notification, audit) to `async`. Keep command-
triggered state changes synchronous. The existing `messenger.yaml` routing table demonstrates this
correctly — extend it per-event.

---

## Build Order (dependency-aware)

This ordering considers which features block others and which are independent:

```
Phase 1: Process Polish + Tech Debt (no new modules — fix existing)
  - Fix formSchema snapshot vs live read
  - Dedup FormSchemaBuilder
  - from_variable enum gap
  - No architecture changes needed

Phase 2: User Profile + Avatar (Identity extension)
  - Self-contained Identity module extension
  - No new cross-module deps
  - Unblocks: avatar display in AuditLog, Dashboard, Notifications

Phase 3: Notification Enhancement (extend existing Notification module)
  - Add Mercure push (NotificationMercurePublisher)
  - Add email port + implementation
  - Add new event handlers (OnProcessStarted, OnProcessCompleted)
  - Depends on: Phase 2 (avatarUrl in notification display)
  - Unblocks: real-time updates for Dashboard

Phase 4: AuditLog Module (new module, pure event consumer)
  - Independent of all above phases
  - Can run parallel with Phase 3 if team allows
  - Depends on: messenger.yaml routing additions for Workflow events

Phase 5: Dashboard (new module, query-only)
  - Depends on: AuditLog table existing (for activity feed)
  - Depends on: Notification badge working (for unread count in summary)
  - Best built after Phase 3 + 4

Phase 6: Timer Node Execution (Workflow completion)
  - Verify and test existing timer infrastructure
  - No architecture changes — verification + test work
  - Can run parallel with Phases 3-5

Phase 7: Super Admin Impersonation (Identity security extension)
  - Lightweight — security.yaml + Voter + ROLE_SUPER_ADMIN
  - Depends on: AuditLog (to log impersonation events)
  - Build after Phase 4

Phase 8: CI/CD Pipeline
  - Pure infrastructure — no module changes
  - Can be set up at any point after Phase 1
  - Recommended: set up early (Phase 1 or 2) to catch regressions
```

---

## Integration Points Summary

| Feature | New Module? | Extends Which? | New Domain Events? | Async? | Frontend Module |
|---------|-------------|---------------|-------------------|--------|-----------------|
| Audit Logging | YES (AuditLog) | — | No (consumes existing) | YES | audit-log/ (simple timeline) |
| Notifications | NO | Notification | ProcessStarted, ProcessCompleted consumers | YES (email) | notifications/ (extend) |
| Dashboard | YES (Dashboard) | — | No | NO (synchronous reads) | dashboard/ (new) |
| User Profile | NO | Identity | UserProfileUpdated (optional) | NO | auth/ (ProfilePage) |
| Timer Node | NO | Workflow | No (already exists) | YES (DelayStamp) | None (backend only) |
| CI/CD | NO (infra) | — | No | No | No |
| Impersonation | NO | Identity | UserImpersonated (new) | NO | Topbar banner |

---

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 0-1k users (current) | Modular monolith fine. Single PostgreSQL. Async via RabbitMQ for notifications. |
| 1k-10k users | AuditLog table grows fast (index on organization_id + occurred_at + entity_id). Notification table needs pruning strategy (archive read > 30d). Dashboard queries need indexes. |
| 10k+ users | AuditLog → separate read replica or separate DB. Notifications → push only (no polling). ProcessInstanceProjection → Redis cache. |

### First Bottleneck

AuditLog will be the first performance concern — every significant action writes a row. Add a
composite index `(organization_id, entity_id, occurred_at DESC)` from day one. Consider a
retention policy (archive entries older than 90 days to cold storage).

### Second Bottleneck

Dashboard DBAL queries run on every page load. Cache with Redis (`symfony/cache`, 30-second TTL)
keyed by `organization_id`. The existing Redis service is already wired in `services.yaml`.

---

## Sources

- Codebase direct analysis — all 9 modules, event handlers, Doctrine mappings, messenger.yaml (HIGH confidence)
- [Symfony Messenger — official docs](https://symfony.com/doc/current/messenger.html) (HIGH confidence)
- [Symfony Security — Impersonation](https://symfony.com/doc/current/security/impersonating_user.html) (HIGH confidence)
- [Domain Events pattern via DispatchDomainEventsMiddleware — existing code](backend/src/Shared/Infrastructure/Bus/Middleware/DispatchDomainEventsMiddleware.php) (HIGH confidence)
- [RabbitMQ Delayed Message Exchange plugin](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange) — needed if DelayStamp precision required (MEDIUM confidence — plugin availability in Docker image unverified)
- [ProcessInstanceProjection DBAL pattern — existing code](backend/src/Workflow/Infrastructure/ReadModel/ProcessInstanceProjection.php) (HIGH confidence)

---

*Architecture research for: Procivo v2.0 Production-Ready BPM Features*
*Researched: 2026-03-01*
