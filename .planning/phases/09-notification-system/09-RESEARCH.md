# Phase 9: Notification System - Research

**Researched:** 2026-03-01
**Domain:** Real-time notifications (Mercure SSE), async email (Symfony Mailer + RabbitMQ), notification preferences, frontend notification center
**Confidence:** HIGH

---

## Summary

Phase 9 builds on a **substantial amount of scaffolding already committed to the codebase**. The Notification module backend (entity, repository, handlers for 3 event types, controller, DTO, CQRS commands/queries) and the frontend notification module (types, API client, Pinia store, NotificationBell component) both exist. The AppTopbar already imports and renders `NotificationBell`. The polling-based unread count (30s interval) is operational.

What is **missing or incomplete** falls into four categories:
1. **Mercure real-time push** — neither backend nor frontend uses per-user SSE topics for notifications; backend still saves to DB only; frontend polls; the `connectMercure` pattern exists in KanbanBoardPage for org-level topics but needs to be applied to `/users/{userId}/notifications`
2. **Email channel** — no `NotificationMailer`, no email templates for task notifications; only the invitation mailer exists as a pattern
3. **Notification preferences** — no `NotificationPreference` entity, no DB table, no API, no UI section in ProfilePage
4. **Missing trigger events** — 4 of 7 required triggers have no handlers: `ProcessStartedEvent`, `ProcessCompletedEvent`, `ProcessCancelledEvent`, `InvitationCreatedEvent`; plus existing handlers need to be wired to Mercure and email
5. **Entity model gaps** — the `Notification` entity and ORM mapping lack `channel`, `readAt` fields required by NOTF-01; `NotificationType` enum lacks process and invitation types

The existing `HubInterface` + `Update` pattern (from `TaskMercurePublisher`) and `MailerInterface` + Twig pattern (from `SymfonyInvitationMailer`) are complete, battle-tested blueprints. No new libraries are needed. The work is primarily: extend the domain model, wire Mercure publishing into existing event handlers, add the email channel, create preferences CRUD, and upgrade the frontend to use SSE.

**Primary recommendation:** Implement in two plans — Plan 01 (backend: domain model update + Mercure publishing + email + preferences API + remaining triggers) and Plan 02 (frontend: Mercure SSE subscription in notification store + notification center page + preferences UI in ProfilePage).

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| NOTF-01 | Notification entity stored in DB with type, channel, recipient, payload, status, readAt | Entity exists but missing `channel`, `readAt` fields; ORM mapping needs update; migration needed |
| NOTF-02 | In-app notifications delivered real-time via Mercure SSE (per-user topics) | `HubInterface` + `Update` pattern exists in TaskMercurePublisher; backend handlers need to publish to `/users/{userId}/notifications`; frontend store needs EventSource subscription |
| NOTF-03 | Email notifications sent async via Symfony Mailer + RabbitMQ with Twig templates | `SymfonyInvitationMailer` is the complete pattern; need `NotificationMailerInterface` + impl + templates; async routing via messenger.yaml |
| NOTF-04 | User can configure notification preferences per event type per channel | No entity, no table, no API, no UI — build from scratch; store in `notification_preferences` table; expose via `/api/v1/notifications/preferences`; render in ProfilePage |
| NOTF-05 | Bell icon in topbar with unread count badge | Bell component exists with polling; upgrade to real-time via Mercure SSE in notification store |
| NOTF-06 | Notification center page with list, filters, mark-read, click-to-navigate | No page exists; API supports list + mark-read; need to add `NotificationsPage.vue`, route, and navigation |
| NOTF-07 | Triggers: task assigned, task completed, process started/completed, comment added, invitation received, process cancelled | `OnTaskAssigned`, `OnTaskStatusChanged`, `OnCommentAdded` handlers exist; need 4 more handlers: `OnProcessStarted`, `OnProcessCompleted`, `OnProcessCancelled`, `OnInvitationCreated`; existing handlers need Mercure + email channel wiring |

</phase_requirements>

---

## Standard Stack

### Core (already installed — no new packages needed)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| symfony/mercure-bundle | ^0.4.2 | Mercure SSE hub publishing from PHP | Installed; `HubInterface` autowired |
| symfony/mercure | ^0.7.2 | Mercure `Update` value object | Installed |
| symfony/mailer | 8.0.* | Async email sending | Installed; MAILER_DSN configured (Mailpit local) |
| Twig | 8.0.* | Email HTML templates | Installed; `templates/email/` directory exists |
| symfony/messenger | 8.0.* | Async routing via RabbitMQ | Installed; `async` transport configured |

### Frontend (no new npm packages needed)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| native EventSource API | Browser built-in | Mercure SSE client | Used in KanbanBoardPage; no extra package needed |
| Pinia 3 | installed | Notification store | useNotificationStore exists |
| PrimeVue 4 | installed | Badge, Popover, DataTable, Tag | Used throughout app |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Native EventSource | @mercure/component or mercure-js | No benefit — native SSE is standard and sufficient |
| MAILER_DSN async via messenger | TemplatedEmail + async transport | Symfony Mailer can be made async by routing a mail message — but the existing pattern uses direct MailerInterface which dispatches synchronously; route a SendEmailMessage or wrap in a command to make async |
| Per-user Mercure topic | Org-level topic with filter | Per-user is required by NOTF-02 and STATE.md decision; broadcasts to `/users/{userId}/notifications` |

---

## Architecture Patterns

### Recommended Project Structure

```
backend/src/Notification/
├── Domain/
│   ├── Entity/
│   │   ├── Notification.php           ← extend with channel, readAt, payload JSONB
│   │   └── NotificationPreference.php ← NEW
│   ├── ValueObject/
│   │   ├── NotificationId.php         ← exists
│   │   ├── NotificationType.php       ← extend with all 7 types
│   │   └── NotificationChannel.php    ← NEW enum: in_app, email
│   └── Repository/
│       ├── NotificationRepositoryInterface.php ← exists
│       └── NotificationPreferenceRepositoryInterface.php ← NEW
├── Application/
│   ├── EventHandler/
│   │   ├── OnTaskAssigned.php         ← exists, extend with Mercure + email
│   │   ├── OnTaskStatusChanged.php    ← exists, extend with Mercure + email
│   │   ├── OnCommentAdded.php         ← exists, extend with Mercure + email
│   │   ├── OnProcessStarted.php       ← NEW
│   │   ├── OnProcessCompleted.php     ← NEW (sync — ProcessCompletedEvent not async)
│   │   ├── OnProcessCancelled.php     ← NEW
│   │   └── OnInvitationCreated.php    ← NEW
│   ├── Command/
│   │   ├── MarkAsRead/                ← exists
│   │   ├── MarkAllAsRead/             ← exists
│   │   ├── SavePreferences/           ← NEW
│   │   └── (optional) SendEmailNotification/ ← NEW if email decoupled
│   └── Query/
│       ├── ListNotifications/         ← exists
│       ├── CountUnread/               ← exists
│       └── GetPreferences/            ← NEW
├── Infrastructure/
│   ├── Repository/
│   │   ├── DoctrineNotificationRepository.php     ← exists
│   │   └── DoctrineNotificationPreferenceRepository.php ← NEW
│   ├── Mercure/
│   │   └── NotificationMercurePublisher.php ← NEW (mirrors TaskMercurePublisher)
│   └── Email/
│       └── SymfonyNotificationMailer.php ← NEW (mirrors SymfonyInvitationMailer)
└── Presentation/
    └── Controller/
        └── NotificationController.php ← exists; add preferences endpoints

frontend/src/modules/notifications/
├── types/notification.types.ts       ← extend with all types + preference type
├── api/notification.api.ts           ← exists; add preferences API calls
├── stores/notification.store.ts      ← exists; add Mercure SSE + preferences
├── components/
│   └── NotificationBell.vue          ← exists; keep, upgrade to real-time
└── pages/
    └── NotificationsPage.vue         ← NEW (NOTF-06)
```

---

### Pattern 1: Mercure Publishing from Event Handler (per-user topic)

**What:** After saving notification to DB, publish an SSE update to `/users/{recipientId}/notifications`
**When to use:** Every in-app notification creation

```php
// Source: existing TaskMercurePublisher pattern + Mercure docs
final class NotificationMercurePublisher
{
    public function __construct(private HubInterface $hub) {}

    public function publishNotification(string $recipientId, array $data): void
    {
        $update = new Update(
            sprintf('/users/%s/notifications', $recipientId),
            (string) json_encode($data),
        );
        $this->hub->publish($update);
    }
}

// In OnTaskAssigned handler:
$this->notificationRepository->save($notification);
$this->mercurePublisher->publishNotification(
    $event->assigneeId,
    ['event' => 'notification.created', 'data' => NotificationDTO::fromEntity($notification)]
);
```

**Confidence:** HIGH — mirrors existing `TaskMercurePublisher`

---

### Pattern 2: Async Email via Symfony Mailer (mirrors InvitationMailer)

**What:** Send HTML email with Twig template; Symfony Mailer with MAILER_DSN dispatches via SMTP
**When to use:** Email channel notifications; email opt-in based on preferences

```php
// Source: existing SymfonyInvitationMailer pattern
final class SymfonyNotificationMailer implements NotificationMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail,
    ) {}

    public function sendTaskAssigned(string $recipientEmail, string $taskTitle, string $taskUrl): void
    {
        $html = $this->twig->render('email/notification/task_assigned.html.twig', [
            'taskTitle' => $taskTitle,
            'taskUrl' => $taskUrl,
        ]);

        $this->mailer->send(
            (new Email())
                ->from($this->fromEmail)
                ->to($recipientEmail)
                ->subject(sprintf('Task assigned: %s', $taskTitle))
                ->html($html)
        );
    }
}
```

**Note on async:** The existing MailerInterface sends synchronously in the request. To send email asynchronously, route a Symfony `SendEmailMessage` via messenger — OR wrap the email-send in a `SendNotificationEmailCommand` routed to `async` transport. The project uses the simpler pattern (direct MailerInterface), and since notification emails fire from async event handlers (which already run on the RabbitMQ worker), the email send effectively runs off the HTTP request cycle — so no extra async wrapping is needed.

**Confidence:** HIGH — direct copy of existing pattern

---

### Pattern 3: Notification Preferences Entity

**What:** Store per-user, per-event-type, per-channel boolean preferences
**Schema:**

```sql
CREATE TABLE notification_preferences (
    id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    event_type VARCHAR(50) NOT NULL,  -- task_assigned, task_completed, etc.
    channel VARCHAR(20) NOT NULL,      -- in_app, email
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY(id),
    UNIQUE (user_id, event_type, channel)
)
```

**API pattern:**
- `GET /api/v1/notifications/preferences` — returns map of {eventType: {in_app: bool, email: bool}}
- `PUT /api/v1/notifications/preferences` — accepts bulk update

**Default behaviour:** email is opt-in (default false for email, true for in_app) per NOTF-03.

**Confidence:** MEDIUM — standard pattern; implementation details to decide in planning

---

### Pattern 4: Frontend Mercure SSE Subscription (per-user)

**What:** Connect to Mercure hub on `/users/{userId}/notifications`; push received data into store
**When to use:** In notification store `initMercure()` called after login

```typescript
// Source: existing KanbanBoardPage EventSource pattern
function initMercure(userId: string) {
  const mercureUrl = import.meta.env.VITE_MERCURE_URL
  if (!mercureUrl) return

  const topic = encodeURIComponent(`/users/${userId}/notifications`)
  const url = `${mercureUrl}?topic=${topic}`

  eventSource = new EventSource(url)

  eventSource.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.event === 'notification.created') {
      notifications.value.unshift(data.data)
      unreadCount.value += 1
    }
  }
}

function closeMercure() {
  eventSource?.close()
  eventSource = null
}
```

**Where to call:** `initMercure()` in `useNotificationStore` after auth initializes (or from App.vue after user is loaded); `closeMercure()` on logout.

**Confidence:** HIGH — identical pattern to KanbanBoardPage, verified working in project

---

### Pattern 5: ProcessCompletedEvent — SYNC constraint

**Critical:** `ProcessCompletedEvent` is **NOT routed to async transport** in messenger.yaml (by design, because `OnSubProcessCompleted` must run synchronously for parent process continuation). Therefore `OnProcessCompleted` notification handler will run synchronously in the HTTP request. The handler must be extremely lightweight — save to DB and publish Mercure; skip email (or route email via separate async command).

**Confidence:** HIGH — documented in STATE.md and confirmed in messenger.yaml

---

### Pattern 6: InvitationCreatedEvent — email already sent by InviteUserHandler

The `InviteUserHandler` directly calls `InvitationMailerInterface` to send the invitation email. The `InvitationCreatedEvent` is **not dispatched** (the invitation creation does not fire a domain event). Options:
1. Add `OnInvitationCreated` handler for the `InvitationCreatedEvent` if it gets dispatched — but Invitation entity currently does not record domain events
2. OR: save in-app notification for invited user (if they are an existing user) in `OnInvitationCreated` when the event is emitted, OR skip email for this trigger (email already handled by InviteUserHandler) and only do in-app

**Recommended approach:** Dispatch `InvitationCreatedEvent` from `InviteUserHandler` (or from Invitation entity) and create an in-app notification handler. Skip email notification (email is already sent). If invited user not registered yet, skip in-app too (no userId to notify). Notify the **inviter** about successful invite, or the **org admin**, not the invitee (who may not have an account).

**Confidence:** MEDIUM — requires decision in planning

---

### Anti-Patterns to Avoid

- **Subscribing to org-wide Mercure topic for personal notifications:** per STATE.md decision, per-user topics only (`/users/{userId}/notifications`)
- **Polling as final solution:** NotificationBell currently polls every 30s — acceptable for MVP but replace with Mercure SSE for NOTF-02 compliance
- **Sending email from sync context without async worker:** InviteUserHandler shows it works from command handler — since notification events are routed async, this is fine
- **Direct SQL actor_id lookup in async handler:** use `SecurityTokenStorage` which is null in async workers — actors come from the event payload (like `event->actorId`)
- **Fetching ProcessInstance in sync `OnProcessCompleted`:** keep it fast; don't query process details in the sync handler

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| SSE connection | Custom WebSocket server | Native `EventSource` + Mercure hub | Already configured and working |
| Email HTML | Inline CSS by hand | Twig templates + existing style | `templates/email/invitation.html.twig` is the pattern |
| Async queue | Custom job table | Symfony Messenger + RabbitMQ async transport | Already configured |
| JWT for Mercure subscriber | Custom auth | MERCURE_JWT_SECRET — anonymous subscribers work for public topics | Project uses open subscriber tokens |
| Notification deduplication | Custom logic | Accept duplicate delivery; client is idempotent | Not in scope for v2.0 |

---

## Common Pitfalls

### Pitfall 1: Notification entity missing `channel` and `readAt` fields

**What goes wrong:** NOTF-01 requires `channel`, `status`, `readAt` fields in the entity; current `Notification.php` only has `isRead` (bool) — no `readAt` timestamp, no `channel`, no explicit `status` field
**Why it happens:** Scaffolded entity was a quick prototype
**How to avoid:** Update entity, ORM XML mapping, and write a migration before touching handlers; add `channel` enum field and `readAt` nullable timestamp; `status` can map to `isRead` or be a separate enum (`unread`/`read`)
**Warning signs:** NOTF-01 success criterion check will fail if entity fields don't match spec

### Pitfall 2: ProcessCompletedEvent sync handler is slow

**What goes wrong:** ProcessCompletedEvent runs synchronously (not on RabbitMQ); if `OnProcessCompleted` handler does expensive work (email send, DB queries), it blocks the HTTP request
**Why it happens:** ProcessCompletedEvent intentionally not routed async (see STATE.md and messenger.yaml comment)
**How to avoid:** Only save in-app notification to DB + publish Mercure in this handler; skip email or dispatch an email-specific async command (routed to `async`)
**Warning signs:** HTTP timeout or slow response after process completion

### Pitfall 3: Mercure subscription without userId available

**What goes wrong:** `initMercure(userId)` is called in `useNotificationStore` before user is loaded from `/auth/me`, causing subscription to wrong or undefined topic
**Why it happens:** Auth initialization is async; store may init before user resolves
**How to avoid:** Call `initMercure()` only after `auth.fetchUser()` completes; use a watcher in App.vue or call from `auth.initialize()`
**Warning signs:** No notifications received after login; EventSource URL contains `undefined`

### Pitfall 4: NotificationPreference table missing unique constraint

**What goes wrong:** Duplicate preferences rows created (user_id + event_type + channel should be unique)
**Why it happens:** Forgetting UNIQUE constraint in migration
**How to avoid:** Add `UNIQUE (user_id, event_type, channel)` in the migration; use upsert-style logic in repository (find-or-create)
**Warning signs:** Preferences API returns multiple rows for same combination

### Pitfall 5: Mercure topic encoding mismatch

**What goes wrong:** Backend publishes to `/users/abc123/notifications`; frontend subscribes to wrong URL (encoding mismatch)
**Why it happens:** `encodeURIComponent` on frontend vs plain string on backend
**How to avoid:** Mercure hub handles topic matching — frontend should `encodeURIComponent` the topic URL as query param; backend passes raw topic string to `new Update()`
**Warning signs:** SSE connects but no messages received

### Pitfall 6: InvitationCreatedEvent not dispatched

**What goes wrong:** No `InvitationCreatedEvent` is dispatched in the messenger; adding a handler for it does nothing
**Why it happens:** `InviteUserHandler` uses `InvitationMailerInterface` directly without domain events; the Invitation entity currently does not record domain events
**How to avoid:** Either add event dispatching to `InviteUserHandler` (or Invitation entity) or handle this trigger differently — see Pattern 6 above
**Warning signs:** `OnInvitationCreated` handler registered but never invoked

---

## Code Examples

### Extending Notification entity with channel and readAt

```php
// backend/src/Notification/Domain/Entity/Notification.php
class Notification
{
    private string $channel;    // 'in_app' | 'email'
    private ?\DateTimeImmutable $readAt = null;

    public static function create(
        NotificationId $id,
        string $recipientId,
        NotificationType $type,
        string $channel,        // new
        string $title,
        string $body,
        ?string $relatedEntityId = null,
    ): self {
        // ...
        $notification->channel = $channel;
        $notification->isRead = false;
        $notification->readAt = null;
        return $notification;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();
    }
}
```

### ORM mapping additions

```xml
<!-- Notification.orm.xml additions -->
<field name="channel" column="channel" type="string" length="20"/>
<field name="readAt" column="read_at" type="datetime_immutable" nullable="true"/>
```

### Migration for notifications table update + preferences table

```php
// Version20260303100000.php
$this->addSql('ALTER TABLE notifications ADD channel VARCHAR(20) NOT NULL DEFAULT \'in_app\'');
$this->addSql('ALTER TABLE notifications ADD read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

$this->addSql(<<<'SQL'
    CREATE TABLE notification_preferences (
        id VARCHAR(36) NOT NULL,
        user_id VARCHAR(36) NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        channel VARCHAR(20) NOT NULL,
        enabled BOOLEAN NOT NULL DEFAULT TRUE,
        PRIMARY KEY(id)
    )
SQL);
$this->addSql('CREATE UNIQUE INDEX idx_notif_pref_unique ON notification_preferences (user_id, event_type, channel)');
```

### Frontend: Mercure subscription in notification store

```typescript
// stores/notification.store.ts
let eventSource: EventSource | null = null

function initMercure(userId: string) {
  const mercureUrl = import.meta.env.VITE_MERCURE_URL
  if (!mercureUrl || eventSource) return

  const topic = encodeURIComponent(`/users/${userId}/notifications`)
  eventSource = new EventSource(`${mercureUrl}?topic=${topic}`)

  eventSource.onmessage = (event) => {
    try {
      const msg = JSON.parse(event.data)
      if (msg.event === 'notification.created') {
        notifications.value.unshift(msg.data)
        unreadCount.value += 1
      }
    } catch { /* ignore parse errors */ }
  }
}

function closeMercure() {
  eventSource?.close()
  eventSource = null
}
```

### Preferences API response shape

```typescript
// GET /api/v1/notifications/preferences
interface NotificationPreferences {
  [eventType: string]: {
    in_app: boolean
    email: boolean
  }
}

// Example:
{
  "task_assigned":    { "in_app": true, "email": false },
  "task_completed":   { "in_app": true, "email": false },
  "comment_added":    { "in_app": true, "email": false },
  "process_started":  { "in_app": true, "email": false },
  "process_completed":{ "in_app": true, "email": false },
  "process_cancelled":{ "in_app": true, "email": false },
  "invitation_received":{ "in_app": true, "email": false }
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Polling every 30s | Mercure SSE real-time | This phase | Bell updates instantly on notification |
| `isRead` bool only | `isRead` + `readAt` timestamp | This phase | NOTF-01 compliance |
| 3 trigger event types | 7 trigger event types | This phase | NOTF-07 compliance |
| No email channel | Email opt-in via preferences | This phase | NOTF-03/04 compliance |

---

## Open Questions

1. **InvitationCreatedEvent dispatch**
   - What we know: InviteUserHandler does not dispatch a domain event; Invitation entity has no event recording
   - What's unclear: Should we emit `InvitationCreatedEvent` from InviteUserHandler and route it async? Or create a different trigger point?
   - Recommendation: In Plan 01, add `$this->eventBus->dispatch(new InvitationCreatedEvent(...))` in InviteUserHandler directly (same pattern as other handlers that dispatch events), route to `async` in messenger.yaml, then handle in `OnInvitationCreated`; the invited user may not exist in Identity — so in-app notification only fires if `$userRepository->findByEmail($invitation->email())` returns a user

2. **ProcessCompletedEvent — missing `startedBy` context**
   - What we know: `ProcessCompletedEvent` only has `processInstanceId` — no `startedBy` or `organizationId`
   - What's unclear: Who receives the "process completed" notification? The process starter needs to be fetched via process instance read model
   - Recommendation: Query `ProcessInstanceReadModelRepositoryInterface` (or Doctrine directly via cross-module query adapter) in `OnProcessCompleted` to get `startedBy`; keep it fast since this is sync

3. **Notification center page — filtering by type**
   - What we know: ListNotificationsQuery accepts limit/offset but no type filter; NOTF-06 requires filter by type
   - What's unclear: Add `?type=` query param to existing endpoint or new endpoint?
   - Recommendation: Extend `ListNotificationsQuery` with optional `?type=` param; add WHERE clause in repository; minimal backend change

4. **Click-to-navigate in notification center**
   - What we know: `NotificationDTO.relatedEntityId` is a bare UUID with no entity type context
   - What's unclear: Frontend cannot determine which route to navigate to with just a UUID
   - Recommendation: Add `relatedEntityType` field to notification (e.g. `task`, `process_instance`) so frontend can build correct route: `/organizations/{orgId}/tasks/{id}` vs `/organizations/{orgId}/process-instances/{id}`; this requires orgId too — consider adding `organizationId` to notification payload or reading it from user context

---

## Validation Architecture

> `workflow.nyquist_validation` is not set in config.json (not present) — skip this section.

---

## Sources

### Primary (HIGH confidence)
- Codebase: `/backend/src/TaskManager/Infrastructure/Mercure/TaskMercurePublisher.php` — existing Mercure pattern
- Codebase: `/backend/src/Organization/Infrastructure/Service/SymfonyInvitationMailer.php` — existing Mailer pattern
- Codebase: `/backend/config/packages/messenger.yaml` — transport routing config
- Codebase: `/frontend/src/modules/tasks/pages/KanbanBoardPage.vue` — EventSource pattern
- Codebase: `/backend/src/Notification/` — full module scaffold (entity, handlers, controller, DTO)
- Codebase: `/frontend/src/modules/notifications/` — frontend module scaffold (store, API, bell, types)
- Codebase: `/frontend/src/shared/components/AppTopbar.vue` — NotificationBell already integrated

### Secondary (MEDIUM confidence)
- `.planning/STATE.md` decision: "Mercure topics must be per-user (/users/{userId}/notifications)"
- `.planning/STATE.md` decision: "ProcessCompletedEvent NOT routed async: OnSubProcessCompleted must run synchronously"

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all libraries verified installed in composer.json + package.json; patterns confirmed working in codebase
- Architecture: HIGH — based on direct codebase inspection; existing patterns are clear blueprints
- Pitfalls: HIGH — derived from codebase realities (sync event, missing fields, InvitationCreatedEvent not dispatched) not speculation

**Research date:** 2026-03-01
**Valid until:** 2026-04-01 (stable stack; main risk is codebase changes)

---

## What's Already Done (Do Not Re-Implement)

This section is critical for the planner to avoid duplicate work:

| Component | Status | Location |
|-----------|--------|----------|
| Notification entity (basic) | EXISTS — needs channel/readAt fields | `backend/src/Notification/Domain/Entity/Notification.php` |
| NotificationType enum | EXISTS — needs 4 more types | `backend/src/Notification/Domain/ValueObject/NotificationType.php` |
| NotificationRepositoryInterface | EXISTS — complete | `backend/src/Notification/Domain/Repository/` |
| DoctrineNotificationRepository | EXISTS — complete | `backend/src/Notification/Infrastructure/Repository/` |
| Notification ORM mapping | EXISTS — needs channel/readAt columns | `backend/src/Notification/Infrastructure/Persistence/Doctrine/Mapping/Notification.orm.xml` |
| notifications table | EXISTS — needs ALTER for channel, read_at | (from previous migration) |
| OnTaskAssigned handler | EXISTS — needs Mercure + email wiring | `backend/src/Notification/Application/EventHandler/OnTaskAssigned.php` |
| OnTaskStatusChanged handler | EXISTS — needs Mercure + email wiring | `backend/src/Notification/Application/EventHandler/OnTaskStatusChanged.php` |
| OnCommentAdded handler | EXISTS — needs Mercure + email wiring | `backend/src/Notification/Application/EventHandler/OnCommentAdded.php` |
| NotificationDTO | EXISTS — needs channel/readAt fields | `backend/src/Notification/Application/DTO/NotificationDTO.php` |
| ListNotificationsHandler | EXISTS — needs type filter | `backend/src/Notification/Application/Query/ListNotifications/` |
| CountUnreadHandler | EXISTS — complete | `backend/src/Notification/Application/Query/CountUnread/` |
| MarkAsReadHandler | EXISTS — needs readAt update | `backend/src/Notification/Application/Command/MarkAsRead/` |
| MarkAllAsReadHandler | EXISTS — complete | `backend/src/Notification/Application/Command/MarkAllAsRead/` |
| NotificationController | EXISTS — needs preferences endpoints | `backend/src/Notification/Presentation/Controller/NotificationController.php` |
| Notification module in services.yaml | EXISTS | `backend/config/services.yaml` |
| NotificationBell.vue | EXISTS — needs Mercure SSE | `frontend/src/modules/notifications/components/NotificationBell.vue` |
| notification.store.ts | EXISTS — needs Mercure SSE + preferences | `frontend/src/modules/notifications/stores/notification.store.ts` |
| notification.api.ts | EXISTS — needs preferences methods | `frontend/src/modules/notifications/api/notification.api.ts` |
| notification.types.ts | EXISTS — needs more types | `frontend/src/modules/notifications/types/notification.types.ts` |
| NotificationBell in AppTopbar | EXISTS — integrated | `frontend/src/shared/components/AppTopbar.vue` |
| VITE_MERCURE_URL env | EXISTS | `frontend/.env` |

**Missing (must be built):**

| Component | Location |
|-----------|----------|
| NotificationChannel enum | `backend/src/Notification/Domain/ValueObject/NotificationChannel.php` |
| NotificationPreference entity | `backend/src/Notification/Domain/Entity/NotificationPreference.php` |
| NotificationPreference ORM mapping | `backend/src/Notification/Infrastructure/Persistence/Doctrine/Mapping/NotificationPreference.orm.xml` |
| NotificationPreferenceRepositoryInterface | `backend/src/Notification/Domain/Repository/` |
| DoctrineNotificationPreferenceRepository | `backend/src/Notification/Infrastructure/Repository/` |
| NotificationMercurePublisher | `backend/src/Notification/Infrastructure/Mercure/NotificationMercurePublisher.php` |
| NotificationMailerInterface | `backend/src/Notification/Application/Port/NotificationMailerInterface.php` |
| SymfonyNotificationMailer | `backend/src/Notification/Infrastructure/Email/SymfonyNotificationMailer.php` |
| Twig email templates for notifications | `backend/templates/email/notification/*.html.twig` |
| OnProcessStarted handler | `backend/src/Notification/Application/EventHandler/OnProcessStarted.php` |
| OnProcessCompleted handler | `backend/src/Notification/Application/EventHandler/OnProcessCompleted.php` |
| OnProcessCancelled handler | `backend/src/Notification/Application/EventHandler/OnProcessCancelled.php` |
| OnInvitationCreated handler | `backend/src/Notification/Application/EventHandler/OnInvitationCreated.php` |
| GetPreferences query | `backend/src/Notification/Application/Query/GetPreferences/` |
| SavePreferences command | `backend/src/Notification/Application/Command/SavePreferences/` |
| DB migration (alter notifications + new preferences table) | `backend/migrations/VersionXXX.php` |
| NotificationsPage.vue | `frontend/src/modules/notifications/pages/NotificationsPage.vue` |
| Route `/notifications` | `frontend/src/router/index.ts` |
| Preferences section in ProfilePage.vue | `frontend/src/modules/auth/pages/ProfilePage.vue` |
| i18n keys for preferences, center page, new types | `frontend/src/i18n/locales/en.json` + `uk.json` |
