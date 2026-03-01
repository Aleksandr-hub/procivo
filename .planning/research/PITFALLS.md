# Pitfalls Research

**Domain:** BPM Platform — Production-Ready Feature Addition (v2.0: Audit Logging, Notifications, Dashboard, Timer Execution, Impersonation, CI/CD)
**Researched:** 2026-03-01
**Confidence:** HIGH (grounded in existing Procivo codebase analysis + verified against official Symfony docs and RabbitMQ documentation)

---

## Critical Pitfalls

### Pitfall 1: Audit Log Writes Inside the Same Transaction as the Command

**What goes wrong:**
Audit log records are written synchronously inside the `DispatchDomainEventsMiddleware` loop, which already runs inside the `doctrine_transaction` middleware on `command.bus`. When the audit insert fails (lock timeout, constraint violation), it rolls back the entire business transaction — reverting the actual command that was supposed to succeed. Alternatively, when the business transaction rolls back due to a domain error, the audit record is also rolled back, creating invisible gaps in the audit trail.

**Why it happens:**
The simplest implementation is to add an `AuditLogSubscriber` to `event.bus` that writes directly to the `audit_log` table. Because `event.bus` currently has no explicit transport routing for domain events produced by `command.bus` handlers (they're dispatched inline via `DispatchDomainEventsMiddleware`), the event handler executes synchronously within the same request — and the same Doctrine connection and transaction.

**How to avoid:**
Route all `AuditableEvent` subtypes to the `async` transport in `messenger.yaml`, so audit writes happen in a separate worker process with their own transaction:
```yaml
routing:
    App\Shared\Domain\AuditableEvent: async
```
Alternatively, if synchronous audit logging is required, use a second Doctrine DBAL connection (`audit_connection`) that operates outside the main ORM transaction. Add a dedicated `AuditConnection` service bound to a separate `DATABASE_AUDIT_URL` env var. The `AuditLogRepository` injects `audit_connection`, not the default `connection`.

**Warning signs:**
- `AuditLogEventHandler` constructor injects `EntityManagerInterface` or the default Doctrine `Connection`
- Audit log test rolls back the database — audit records disappear with it
- A failed process start (invalid definition) still creates an audit entry

**Phase to address:**
Phase covering Audit Logging — design the routing strategy before writing the first audit handler.

---

### Pitfall 2: Notification Flood from WorkflowEngine Events

**What goes wrong:**
A workflow process with 10 sequential task nodes fires a `TokenMovedEvent` at each step. Each movement may trigger multiple event handlers: `ProcessInstanceProjection`, `AuditLog`, and — after v2.0 — `NotificationEmailHandler`. If email is sent on each `TaskStatusChangedEvent` and the workflow engine auto-advances tokens quickly (e.g., gateway routing through 3 nodes), one user action generates 5+ emails within seconds. Users unsubscribe or mark the domain as spam.

The existing `OnTaskStatusChanged` handler already fires for every status transition. Once email is added via `SymfonyMailer`, a single task completion call chain triggers: `TaskStatusChangedEvent` → `OnTaskStatusChanged` → creates DB notification → [new] `NotificationEmailHandler` → sends email. Then `OnWorkflowTaskCompleted` → `WorkflowEngine.resumeToken()` → moves through gateway → creates new task → fires `TaskAssignedEvent` → `OnTaskAssigned` → creates DB notification → [new] second email.

**Why it happens:**
Event handlers are registered independently with no coordination. Each handler is correct in isolation. The flood emerges from the composition of individually-correct handlers.

**How to avoid:**
1. Implement a `NotificationPreference` entity with per-user, per-type opt-in/opt-out (stored in `notification_preferences` table). Email is opt-in, not opt-in by default.
2. Add a debounce / digest mechanism: instead of sending email per event, enqueue a `SendDigestEmailMessage` with a 5-minute delay (using `DelayStamp`). Before sending, check if a more recent event for the same user+context already enqueued — if so, cancel the older one or merge.
3. For workflow-internal transitions (token moves through gateways, auto-advances), do NOT fire user-facing notifications. Only fire when the process lands on a user-actionable node (`TaskNodeActivatedEvent`, `ProcessCompletedEvent`).
4. Use Symfony's RateLimiter on the email notification handler:
```php
$limiter = $this->rateLimiterFactory->create('email_per_user_' . $userId);
if (!$limiter->consume(1)->isAccepted()) {
    return; // skip, user already notified recently
}
```

**Warning signs:**
- `OnTaskStatusChanged` and `OnTaskAssigned` both send emails without checking preferences
- No `notification_preferences` table in migrations
- Manual test of a 3-node workflow generates 6+ email entries in Mailpit

**Phase to address:**
Phase covering Notification System — notification preferences and email rate limiting must be designed before any `MailerInterface` call is added.

---

### Pitfall 3: RabbitMQ Delayed Messages are Not Reliable for Process Timers

**What goes wrong:**
The existing `RabbitMqTimerService` uses Symfony Messenger's `DelayStamp`, which translates to RabbitMQ's delayed message exchange plugin (`rabbitmq_delayed_message_exchange`). This plugin has well-documented production limitations:

1. Delayed messages are stored in Mnesia (not RabbitMQ's normal message store). If the node hosting the exchange goes down, **all pending timer messages are lost permanently** — no HA replication.
2. The plugin uses in-memory Mnesia tables. With many scheduled timers, RAM fills up with no eviction mechanism.
3. Maximum delay is approximately 49 days (Erlang integer limit). Timers beyond this fire immediately.
4. As of RabbitMQ 4.x, the plugin is unmaintained and will be removed in 4.3/4.4 (Mnesia is being retired).

For a BPM timer node, a lost message means the process token sits in `waiting` state forever with no user-visible error.

**Why it happens:**
`DelayStamp` is the documented Symfony Messenger approach. It works in development. The reliability gap only surfaces under node restarts or high timer volumes in production.

**How to avoid:**
Use a **persistent timer table** as the source of truth alongside the async message:

1. Create a `workflow_scheduled_timers` table: `(id, process_instance_id, token_id, node_id, fire_at, status, scheduled_at)`.
2. `OnTimerScheduled` handler inserts a row into this table AND dispatches the `DelayStamp` message as a best-effort accelerator.
3. Add a Symfony Console command `app:workflow:fire-overdue-timers` that queries `WHERE fire_at <= NOW() AND status = 'pending'` and re-dispatches `FireTimerMessage` for any overdue timer. Schedule via cron every 1–5 minutes.
4. `FireTimerHandler` marks the timer row as `fired` when it completes successfully.
5. If RabbitMQ drops the message, the cron job catches it within 1–5 minutes.

This pattern makes timers reliable without requiring a production-grade delayed queue infrastructure.

**Warning signs:**
- `FireTimerMessage` is the only record of a pending timer — no DB table tracks scheduled timers
- Restarting Docker containers in development loses all pending timers silently
- No test verifies that a timer fires after a RabbitMQ restart

**Phase to address:**
Phase covering Timer Node Execution — the persistent timer table must be created before any timer-bearing process templates are used in QA.

---

### Pitfall 4: Super Admin Impersonation is Incompatible with Stateless JWT

**What goes wrong:**
Symfony's built-in `switch_user` firewall feature requires a stateful session to maintain `SwitchUserToken` (which wraps the original admin token) across requests. The Procivo API firewall is configured as `stateless: true` with JWT authentication. When `switch_user` is activated on a stateless firewall, the impersonated context exists only for that single request — the next request re-authenticates from the JWT header and returns to the admin identity. The `IS_IMPERSONATOR` role check fails on subsequent requests, and the impersonation exit mechanism does not work.

This is confirmed in the official Symfony docs: "User impersonation is not compatible with some authentication mechanisms where authentication is expected on each request." LexikJWTAuthenticationBundle issue #652 and #1196 document the exact failure mode.

**Why it happens:**
Developers see `switch_user: true` in security.yaml examples and assume it works with any firewall configuration. The limitation is only documented in a note, not as a hard error.

**How to avoid:**
Implement impersonation as an explicit API operation that **issues a new short-lived JWT for the target user**, with the impersonator's identity embedded as a claim:

```php
// POST /api/v1/admin/impersonate/{userId}
// Requires ROLE_SUPER_ADMIN
// Returns: { token: "eyJ...", impersonating: { userId, email } }
$payload = [
    'sub' => $targetUser->id(),
    'roles' => $targetUser->roles(),
    'impersonated_by' => $adminUser->id(),
    'exp' => time() + 3600, // 1 hour max
];
```

The frontend stores this token separately (e.g., `localStorage.impersonationToken`) and includes an "Exit Impersonation" banner that discards it and restores the original admin JWT.

The `impersonated_by` claim must be logged in every audit log entry written while the impersonation token is active — this is the critical audit requirement for impersonation.

**Warning signs:**
- `security.yaml` adds `switch_user: true` to the `api` firewall which is `stateless: true`
- No test verifies that impersonation persists across two API calls
- Impersonation audit log entry is missing the `impersonated_by` field

**Phase to address:**
Phase covering Super Admin Impersonation — must design the JWT-based approach from the start, not retrofit after discovering switch_user doesn't work.

---

### Pitfall 5: Audit Log Table Becomes a Write Bottleneck

**What goes wrong:**
Every domain event across all 9 modules writes an `audit_log` row. A single user action (complete a workflow task) produces: `TaskStatusChangedEvent`, `TokenMovedEvent`, `TaskNodeActivatedEvent`, `TaskCreatedEvent`, `VariablesMergedEvent` — potentially 5+ rows per user action. With 50 concurrent users each doing 2 actions/minute, that is 500+ inserts/minute. At 10K users, 100K inserts/minute. Standard B-tree indexes on `(user_id, organization_id, occurred_at)` slow every insert by 3-5 ms per index, compounding under write load.

**Why it happens:**
Audit log is an afterthought. The table is created with the same indexing strategy as query tables, not as an append-only write-optimized store.

**How to avoid:**
1. Use BRIN (Block Range INdex) on `occurred_at` — optimal for append-only time-series data, minimal write overhead.
2. Defer non-critical indexes (e.g., full-text search on `message`) to separate maintenance windows.
3. Partition the `audit_log` table by month using PostgreSQL range partitioning: `PARTITION BY RANGE (occurred_at)`. Create partitions via a scheduled job.
4. Never add foreign key constraints on `audit_log` — FKs on high-insert tables serialize writes.
5. Keep the `changes` column as JSONB but avoid GIN indexes on it unless full audit search is a product requirement.

Schema recommendation:
```sql
CREATE TABLE audit_log (
    id          UUID NOT NULL DEFAULT gen_random_uuid(),
    occurred_at TIMESTAMPTZ NOT NULL,
    event_type  VARCHAR(100) NOT NULL,
    actor_id    UUID,
    actor_email VARCHAR(255),
    impersonated_by UUID,  -- null unless impersonation active
    organization_id UUID,
    aggregate_id    UUID,
    aggregate_type  VARCHAR(100),
    changes         JSONB
) PARTITION BY RANGE (occurred_at);

CREATE INDEX audit_log_occurred_at_brin ON audit_log USING BRIN (occurred_at);
CREATE INDEX audit_log_actor_id ON audit_log (actor_id, occurred_at DESC);
CREATE INDEX audit_log_aggregate ON audit_log (aggregate_type, aggregate_id);
```

**Warning signs:**
- Migration creates `audit_log` with `id SERIAL PRIMARY KEY` and multiple B-tree indexes
- No partitioning in the initial migration
- `INSERT INTO audit_log` latency increases visibly after 100K rows in load testing

**Phase to address:**
Phase covering Audit Logging — table schema and partitioning strategy must be in the initial migration, not added later.

---

### Pitfall 6: Dashboard Queries Cross Module Boundaries Through ORM Entities

**What goes wrong:**
The dashboard needs: "My active tasks count", "Active processes count", "My recently completed tasks", "Processes started this week". The natural implementation is to inject `TaskRepositoryInterface` and `ProcessInstanceRepositoryInterface` into a `DashboardQueryHandler` and call them in sequence. This creates a hidden cross-module dependency in the Application layer (Dashboard depends on TaskManager's domain repository), which violates the bounded context isolation in Clean Architecture. It also produces N+1 queries: for each process instance, a second query loads related tasks.

**Why it happens:**
CQRS query handlers can technically inject any repository. The bounded context violation is not enforced by Symfony's DI container. The module folder structure gives a false sense of isolation.

**How to avoid:**
Use **dedicated read model views** at the database level instead of cross-module repository calls:

1. Create a `DashboardModule` (or `Dashboard` namespace in Shared) that owns only query handlers and SQL queries — no domain entities.
2. Write raw DBAL queries against the existing tables (`tasks`, `workflow_process_instances_view`) using joins, not ORM:
```php
// DashboardQueryHandler uses Connection, not repositories
$stats = $this->connection->fetchAssociative(
    'SELECT
        COUNT(*) FILTER (WHERE t.assignee_id = :userId AND t.status NOT IN (\'done\', \'cancelled\')) as my_tasks,
        COUNT(*) FILTER (WHERE piv.started_by = :userId AND piv.status = \'running\') as my_processes
     FROM tasks t
     LEFT JOIN workflow_task_links wtl ON wtl.task_id = t.id
     LEFT JOIN workflow_process_instances_view piv ON piv.id = wtl.process_instance_id
     WHERE t.organization_id = :orgId',
    ['userId' => $userId, 'orgId' => $organizationId]
);
```
3. All dashboard queries must be scoped by `organization_id` — the dashboard is an organization-level view, not a global view.

**Warning signs:**
- `DashboardQueryHandler` constructor injects `TaskRepositoryInterface` and `ProcessInstanceRepositoryInterface`
- `explain analyze` on the dashboard query shows sequential scans or N+1 patterns
- Dashboard data includes tasks from other organizations

**Phase to address:**
Phase covering Dashboard — the DBAL-direct approach must be decided before writing any handler.

---

### Pitfall 7: Mercure Topic Leaks Between Users

**What goes wrong:**
The existing `TaskMercurePublisher` publishes to `/organizations/{organizationId}/tasks` — a shared organization-wide topic. Any browser in the same organization subscribes to all task updates for all users, including tasks assigned to others. When the Notification module adds in-app notification publishing (bell icon), if it uses the same or a similarly broad topic, User A receives User B's notification content in their EventSource stream — even if the notification is not shown in the UI.

**Why it happens:**
Organization-scoped topics are the natural first step: they match the existing RBAC model. Per-user topics feel like premature optimization. The data leak only becomes obvious when notification content (e.g., "HR review: salary adjustment approved") is included in the SSE payload.

**How to avoid:**
Use **two-tier topics**:
- Public (organization-wide): `/organizations/{orgId}/activity` — for board-level task state changes that all members need (task status changed on Kanban board). Payload contains only IDs and status, never content.
- Private (per-user): `/users/{userId}/notifications` — for personal notifications (assigned to you, mentioned, task completed by you). Payload may contain content.

The Mercure subscriber JWT must include only the topics the user is authorized to subscribe to:
```php
// JWT claim for subscriber
[
    'mercure' => [
        'subscribe' => [
            '/organizations/' . $orgId . '/activity',
            '/users/' . $userId . '/notifications',
        ]
    ]
]
```

For the notification bell, publish exclusively to `/users/{userId}/notifications` — never to the organization topic.

**Warning signs:**
- Notification `MercurePublisher` uses the same organization-wide topic as task updates
- Subscriber JWT uses wildcard subscribe claim (`*` or `/organizations/*`)
- User B's browser console shows SSE events intended for User A when both are in the same org

**Phase to address:**
Phase covering Notification System — topic hierarchy must be defined before any notification Mercure publisher is implemented.

---

### Pitfall 8: S3 Avatar Upload Has No Server-Side File Type Validation

**What goes wrong:**
The avatar upload endpoint accepts a file from the browser and stores it in S3 via `S3FileStorage.upload()`. The existing `S3FileStorage` takes a `$mimeType` parameter from the caller — likely from the HTTP request's `Content-Type` header or the PHP `UploadedFile::getMimeType()` which relies on the browser-supplied MIME type. An attacker uploads a PHP file with `Content-Type: image/jpeg`. The file is stored in S3 and a presigned URL is returned. If the S3 bucket has `GetObject` accessible and the CDN serves the file with the original content type, the PHP is not executed, but the bucket now contains potentially malicious content.

A second scenario: the presigned URL for the avatar has a 1-hour expiry (as seen in `S3FileStorage.getUrl()`). Avatar URLs embedded in API responses expire after 1 hour. The frontend caches the URL. An hour later, all avatar images return 403 — breaking the UI silently.

**Why it happens:**
Browser MIME type is not trustworthy. Server-side magic byte validation is rarely added during initial implementation. Presigned URL expiry is set to a convenient short value for security but the UI implications are not considered.

**How to avoid:**
1. **Server-side magic byte validation**: After receiving the uploaded file bytes, validate with `finfo_buffer()` to check actual file type:
```php
$finfo = new \finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->buffer($fileContent);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    throw new InvalidAvatarException('Only JPEG, PNG, WebP are allowed');
}
```
2. **File size limit**: Enforce max 5MB at the controller layer before streaming to S3.
3. **Presigned URL strategy for avatars**: Avatars are displayed repeatedly. Options:
   - Store avatar via a public ACL bucket path (e.g., `avatars/` prefix with public read policy) and return the static public URL — no expiry.
   - Or use a longer presigned URL expiry (24h) and cache in frontend state, refreshing on page load.
   - The current 1-hour expiry is appropriate for task attachments (private, sensitive), but wrong for avatar images.
4. **UUID filename**: Always generate a UUID key for the S3 path (`avatars/{userId}/{uuid}.jpg`), never use the original filename.

**Warning signs:**
- Avatar upload controller reads MIME type from `$request->files->get('avatar')->getMimeType()` without `finfo` validation
- Avatar URL in API response expires within 1 hour and frontend does not refresh it
- No max file size check before calling `S3FileStorage::upload()`

**Phase to address:**
Phase covering User Profile + Avatar — validation and URL strategy must be decided at the start of implementation.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Write audit logs synchronously in the same DB transaction | No async infrastructure needed | Audit write failures rollback business commands; business rollbacks silently skip audit | Never — use async transport or separate connection |
| Notification preferences default opt-in for all channels | No preference UI needed in MVP | Email flood leads to spam complaints; users disable notifications globally | Never for email — opt-in by default |
| Single Mercure topic per organization for all events | Simpler publisher code | Personal notification content (salary, HR decisions) leaks to all org members | Never for private notification content |
| Dashboard reads from ORM entities across modules | Faster to write | Cross-module dependency in Application layer; N+1 queries hidden inside repository calls | Never — use DBAL direct queries for cross-module aggregation |
| Timer reliability from DelayStamp alone (no persistent table) | No extra migration | Lost timers on RabbitMQ restart; no recovery path; process tokens stuck forever | Only if timer loss is acceptable (non-production) |
| Using switch_user for impersonation on stateless JWT firewall | Matches Symfony docs | Works only for single request; subsequent requests lose impersonation context | Never — implement explicit JWT-based impersonation |
| Use `actor` data from Symfony Security token in async event handler | No extra context passing | In async workers, Security token is not available — actor resolves to null | Never for async handlers — pass actor ID in the event payload |
| Avatar presigned URL with 1-hour expiry | Good for attachments | Avatars displayed in list views break silently after 1 hour | Only for one-time download links, not display assets |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Symfony Messenger async worker + Security context | Calling `$this->security->getUser()` inside async event handler — returns null because there is no HTTP request | Pass `actorId` and `actorEmail` as explicit fields on every domain event that needs audit attribution |
| Symfony Mailer + Messenger async routing | `$mailer->send()` when Messenger is configured routes email via `SendEmailMessage` to `async` transport automatically — but if the Mailer transport DSN is sync, it sends inline despite Messenger routing | Verify `MAILER_DSN` and Messenger routing together; add an integration test that checks email lands in the `async` queue, not sent synchronously |
| RabbitMQ delayed plugin + Symfony `DelayStamp` | `DelayStamp` uses `x-delay` header which requires the delayed exchange plugin; if plugin is not installed, messages are delivered immediately with no error | Test with plugin disabled to verify fallback behavior; document the plugin as a required dependency |
| Mercure subscriber JWT + frontend EventSource | Browser EventSource API does not support setting Authorization headers; Mercure subscriber JWT must be passed as a cookie or as the `?authorization=` query parameter | Use HttpOnly cookie for JWT in browser clients as recommended by Mercure spec; do NOT put JWT in URL (visible in server logs) |
| PostgreSQL `audit_log` partitioning + Doctrine migrations | Doctrine migrations do not automatically create child partitions for future months — only the initial `CREATE TABLE ... PARTITION BY RANGE` statement | Add a `CreateAuditPartitionsCommand` CLI command that creates next-month partition at the end of each month; schedule via cron |
| `DispatchDomainEventsMiddleware` + new AuditLog event handlers | Domain events dispatched by middleware are routed based on `messenger.yaml` routing rules; if a new `AuditableEvent` base class is not added to routing, it executes synchronously on the event bus (in-memory, no async) | Add explicit routing for `App\Shared\Domain\AuditableEvent: async` BEFORE registering any audit event handlers |
| GitHub Actions + Docker Compose services | Tests that hit the database require PostgreSQL to be healthy before running; `services:` in GitHub Actions does not guarantee health; use `wait-for-it.sh` or `healthcheck` | Use `options: --health-cmd pg_isready --health-interval 5s --health-retries 5` in the service definition |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Audit log B-tree index on `occurred_at` under high insert volume | INSERT latency increases linearly; `EXPLAIN ANALYZE` shows index maintenance cost | Use BRIN index on `occurred_at`; defer GIN index on `changes` JSONB | ~10K inserts/hour |
| Dashboard query without `organization_id` filter | Full table scan on `tasks` across all organizations; slow for any multi-tenant data | Every dashboard query must include `WHERE t.organization_id = :orgId` with an index on that column | First organization with >1000 tasks |
| `ProcessInstanceProjection.updateToken()` issues SELECT+UPDATE per event | Each token event reads the full JSONB `tokens` column, deserializes, modifies, serializes, writes back; expensive under parallel gateway or long processes | Acceptable for current single-token processes; must be revisited before parallel gateway is activated | Processes with >10 active tokens simultaneously |
| Notification count query on every page load | `CountUnreadHandler` executes `SELECT COUNT(*)` on notifications table per request; with 100 concurrent users this is 100 COUNT queries/second | Add Redis cache for unread count: invalidate on new notification or mark-as-read; `Cache-Control: no-store` is acceptable but add a 30-second server-side cache | >20 concurrent users with active notifications |
| Email sending blocking async worker thread | A slow SMTP server (>5s response) ties up one Messenger worker for 5 seconds; with many notifications queued, worker pool saturates | Set Mailer transport timeout to 3s; use a transactional email service (Mailpit in dev, SES/Postmark in prod) with fast response times | >10 emails/minute per worker |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Impersonation JWT without `impersonated_by` claim | Admin can impersonate any user with no audit trail; cannot detect abuse post-incident | Every impersonation JWT MUST include `impersonated_by: <adminUserId>` claim; every audit log entry written under impersonation must record both the actor and the original admin |
| Impersonation endpoint accessible to non-super-admin roles | Any admin can impersonate any user including other admins; privilege escalation | Restrict `POST /admin/impersonate/{userId}` to `ROLE_SUPER_ADMIN` via `access_control`; add a voter that prevents impersonating another super admin |
| Audit log writeable by the application user | Malicious actor with DB credentials can modify audit trail (defeats the purpose) | Use a separate PostgreSQL role for audit writes (`audit_writer`) with `INSERT`-only permissions; never `UPDATE` or `DELETE` on `audit_log`; application DB user does not have `DELETE` on `audit_log` |
| Avatar stored with user-supplied filename in S3 | Path traversal via filename (`../../config/secrets.php`); filename enumeration | Always generate UUID-based S3 key; ignore original filename completely |
| Notification preferences API allows setting other users' preferences | Authenticated user A sets User B's preferences to `email: false`, silencing B's notifications | `UpdateNotificationPreferencesHandler` must verify `command->userId === currentUser->id()` before saving |
| Mercure subscriber JWT issued without topic restrictions | Subscriber receives ALL organization events, including private notifications of other users | Subscriber JWT must enumerate exact topics: `/organizations/{orgId}/activity` and `/users/{userId}/notifications` — never a wildcard |
| GitHub Actions workflow exposes `DATABASE_URL` in logs | `run: php bin/console doctrine:migrations:migrate` can print the DSN in error output | Use GitHub Secrets for all DSNs; ensure `secrets: inherit` is not set globally on all jobs; review step output for credential leaks |

---

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Bell icon count cached in frontend store but not updated after mark-as-read | User marks notification as read; count stays at old value until page refresh | After `MarkAsRead` API call, invalidate or decrement the count in Pinia store immediately (optimistic update) |
| Notification list shows workflow-internal events (token moved, gateway evaluated) | Users see cryptic technical events they cannot act on | Filter notification types: only `TaskAssigned`, `TaskStatusChanged`, `ProcessCompleted`, `MentionedInComment` are user-visible; all engine-internal events go to audit log only |
| Dashboard charts show all-time counts with no time range selector | "Active processes: 1,247" is confusing if most are months old | Default dashboard to "This week" time range; show trend arrow vs. previous week |
| Impersonation active with no visible indicator | Super admin forgets they are impersonating; takes actions as the wrong user | Show a persistent orange banner "Impersonating [user email]" with a single-click exit button whenever impersonation JWT is active |
| Timer countdown not visible on timer node tasks | User has no idea why the process is paused; assumes the system is broken | Process context card should show "Waiting for timer: fires in 2h 15m" when the active token is on a Timer node |

---

## "Looks Done But Isn't" Checklist

- [ ] **Audit logging:** Entries appear in the log — verify they survive a failed business transaction (audit must NOT rollback with the command)
- [ ] **Audit logging:** Actor is populated — verify it is not null when called from an async worker (Security context is absent in workers)
- [ ] **Notification email:** Email is sent — verify it is NOT sent when the user has opted out in preferences
- [ ] **Notification email:** Email appears in Mailpit — verify it does NOT duplicate when the same event is retried by Messenger
- [ ] **Timer execution:** Timer fires in dev — verify it fires after RabbitMQ is restarted (persistent timer table must exist)
- [ ] **Timer execution:** `FireTimerHandler` runs — verify it is idempotent (calling it twice on the same token does not advance the process twice)
- [ ] **Impersonation:** Login as impersonated user works in one request — verify the impersonated identity persists across at least 3 consecutive API calls using the impersonation JWT
- [ ] **Impersonation audit:** Action taken during impersonation — verify audit log row contains both `actor_id` (impersonated user) and `impersonated_by` (admin)
- [ ] **Dashboard:** Counts display — verify they are scoped to the current organization (no cross-org data leakage)
- [ ] **Avatar upload:** Image displays — verify it still displays 2 hours after upload (presigned URL expiry handled)
- [ ] **Mercure notifications:** Bell count updates in real-time — verify User A's browser does NOT receive User B's private notification
- [ ] **CI pipeline:** All tests pass locally — verify the pipeline passes on a clean checkout with no local environment variables

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Audit log writes mixed with business transactions — gaps discovered | HIGH | Write a reconciliation script that replays Workflow event store (`workflow_process_events`) to reconstruct missing audit entries; mark synthetic entries as `source: replayed` |
| Notification flood already reached users (emails sent) | MEDIUM | Add opt-out link in email footer immediately; implement preferences table as emergency hotfix; run SQL to opt-out all users by default; re-opt-in based on user action |
| Timer messages lost after RabbitMQ restart | HIGH | Query `workflow_process_instances_view` for instances with status `running` and tokens in `waiting` state; cross-reference against timer node IDs; manually dispatch `FireTimerMessage` for each overdue timer |
| switch_user impersonation discovered not working on stateless firewall | MEDIUM | Remove `switch_user` from security.yaml; implement JWT-based impersonation endpoint as described in Pitfall 4; any impersonation actions taken via broken mechanism have no audit trail — log a security incident |
| Dashboard query missing `organization_id` filter exposing cross-org data | HIGH | Emergency hotfix to add filter; review all other dashboard queries for same issue; notify affected organizations; rotate any sensitive data that was exposed |
| S3 avatar with malicious file content discovered | MEDIUM | Delete the S3 object immediately; add `finfo` server-side validation; scan all existing uploaded avatars with a magic byte validator; reset affected user avatar to default |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Audit log same-transaction writes | Audit Logging phase | Integration test: failed command does NOT create audit entry; succeeded command creates audit entry that survives transaction |
| Notification flood | Notification System phase | End-to-end test: complete 3-node workflow; assert exactly 1 email sent (task assigned), not 5+ |
| RabbitMQ timer reliability | Timer Execution phase | Test: schedule timer, restart RabbitMQ container, run cron fallback — assert timer fires within 5 minutes |
| JWT impersonation incompatibility | Super Admin Impersonation phase | Test: POST /impersonate, use returned JWT for 3 calls — assert all 3 return impersonated user identity |
| Audit log write bottleneck | Audit Logging phase | Migration review: BRIN index on `occurred_at`, no FK constraints, partitioning strategy present |
| Dashboard cross-module coupling | Dashboard phase | Architecture review: `DashboardQueryHandler` injects only `Connection`, not any module repository |
| Mercure topic leaks | Notification System phase | Test: User A subscribes; User B receives notification — assert User A EventSource does NOT receive User B's `/users/{B}/notifications` events |
| S3 avatar validation | User Profile phase | Test: upload a PHP file as avatar — assert 422 rejection; upload valid JPEG — assert URL still valid after 2 hours |

---

## Sources

- Procivo codebase: `backend/config/packages/messenger.yaml` — confirmed sync event bus routing, `doctrine_transaction` middleware scope
- Procivo codebase: `backend/src/Shared/Infrastructure/Bus/Middleware/DispatchDomainEventsMiddleware.php` — confirms events dispatch synchronously within command transaction
- Procivo codebase: `backend/src/Workflow/Infrastructure/Timer/RabbitMqTimerService.php` — confirms `DelayStamp`-only timer implementation (no persistent fallback)
- Procivo codebase: `backend/config/packages/security.yaml` — confirms `stateless: true` on API firewall (blocks switch_user)
- Procivo codebase: `backend/src/TaskManager/Infrastructure/Mercure/TaskMercurePublisher.php` — confirms organization-wide topic scope
- Procivo codebase: `backend/src/TaskManager/Infrastructure/Storage/S3FileStorage.php` — confirms 1-hour presigned URL expiry
- Official Symfony docs: [Impersonating a User](https://symfony.com/doc/current/security/impersonating_user.html) — confirmed stateless incompatibility
- LexikJWTAuthenticationBundle: [Issue #652](https://github.com/lexik/LexikJWTAuthenticationBundle/issues/652) and [#1196](https://github.com/lexik/LexikJWTAuthenticationBundle/issues/1196) — confirmed JWT + switch_user failure mode
- CloudAMQP: [Pitfalls of the Delayed Message Exchange](https://www.cloudamqp.com/blog/how-to-avoid-the-pitfalls-of-the-delayed-message-exchange.html) — Mnesia reliability, memory exhaustion, node failure data loss
- RabbitMQ GitHub: [rabbitmq-delayed-message-exchange](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange) — confirmed plugin unmaintained, Mnesia removal in 4.3/4.4
- Symfony docs: [Rate Limiter](https://symfony.com/doc/current/rate_limiter.html) — token bucket approach for notification rate limiting
- Detectify Labs: [Bypassing Bucket Upload Policies and Signed URLs](https://labs.detectify.com/writeups/bypassing-and-exploiting-bucket-upload-policies-and-signed-urls/) — S3 content type bypass attack vectors
- Medium: [Production-Ready Audit Logs in PostgreSQL](https://medium.com/@sehban.alam/lets-build-production-ready-audit-logs-in-postgresql-7125481713d8) — BRIN index, partitioning strategy for append-only logs
- Mercure spec: [Topic Authorization](https://mercure.rocks/spec) — subscriber JWT topic selectors for per-user security

---
*Pitfalls research for: BPM Platform v2.0 Production-Ready Features (Procivo)*
*Researched: 2026-03-01*
