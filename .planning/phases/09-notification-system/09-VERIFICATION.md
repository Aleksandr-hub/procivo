---
phase: 09-notification-system
verified: 2026-03-01T20:00:00Z
status: passed
score: 11/11 must-haves verified
re_verification: false
human_verification:
  - test: "Bell icon updates in real-time"
    expected: "After a task is assigned, the bell badge increments without page refresh"
    why_human: "Requires live Mercure SSE connection and a real event trigger — cannot verify programmatically"
  - test: "Email delivery when preference enabled"
    expected: "Enabling email for task_assigned and creating an assignment sends an HTML email via Mailpit"
    why_human: "Requires live Mailer transport + async queue consumption — cannot verify programmatically"
  - test: "Click-to-navigate from notifications page"
    expected: "Clicking a 'task' notification navigates to /organizations/{orgId}/tasks/{taskId}"
    why_human: "Requires browser navigation and a populated org context — cannot verify programmatically"
---

# Phase 09: Notification System Verification Report

**Phase Goal:** Implement notification system with in-app, email, and Mercure SSE real-time channels, user notification preferences, notification center page with filters, and 7 business event triggers.
**Verified:** 2026-03-01T20:00:00Z
**Status:** passed
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Notification entity has channel, readAt, relatedEntityType fields persisted in DB with correct ORM mapping | VERIFIED | `Notification.php` L19-21 has `$channel`, `$readAt`, `$relatedEntityType`; ORM XML L19-22 maps all three columns; migration `Version20260301200000.php` adds them via ALTER TABLE |
| 2 | NotificationPreference entity stores per-user, per-event-type, per-channel enabled/disabled with unique constraint | VERIFIED | `NotificationPreference.php` has userId/eventType/channel/enabled fields; ORM XML has unique-constraint on (user_id, event_type, channel); migration creates `notification_preferences` table |
| 3 | All 7 trigger events create in-app notifications | VERIFIED | Handlers exist and call `notificationDispatcher->dispatch()`: OnTaskAssigned, OnTaskStatusChanged, OnCommentAdded (3 refactored); OnProcessStarted, OnProcessCompleted, OnProcessCancelled, OnInvitationCreated (4 new) |
| 4 | Event handlers publish Mercure SSE updates to /users/{recipientId}/notifications after saving notification | VERIFIED | `NotificationDispatcher.php` L64-67 calls `$this->mercurePublisher->publishNotification($recipientId, ...)` after saving; `NotificationMercurePublisher.php` L22 uses topic `/users/%s/notifications` |
| 5 | Email channel sends async email via Symfony Mailer with Twig templates — only when user preference enabled | VERIFIED | `NotificationDispatcher.php` L74-85: checks `isEnabled($recipientId, type, 'email')` before calling `$this->notificationMailer->send()`; 7 Twig templates exist in `backend/templates/email/notification/` |
| 6 | Preferences CRUD API: GET returns map with defaults, PUT accepts bulk update | VERIFIED | `NotificationController.php` L72-94 has `GET /preferences` (dispatches GetPreferencesQuery) and `PUT /preferences` (dispatches SavePreferencesCommand); `GetPreferencesHandler` iterates `NotificationType::cases()` with defaults in_app=true, email=false |
| 7 | List notifications API supports optional type filter | VERIFIED | `NotificationController.php` L36 reads `$type = $request->query->get('type')`; `ListNotificationsHandler` calls `findByRecipientIdAndType()` which adds `AND n.type = :type` when type is not null |
| 8 | Bell icon unread count updates in real-time via Mercure SSE — no polling dependency | VERIFIED | `notification.store.ts` L50-72 has `initMercure()` using native `EventSource` on `/users/{userId}/notifications`; `NotificationBell.vue` has no `setInterval`/`clearInterval`; `DashboardLayout.vue` L13-23 watches `authStore.user` to call `initMercure`/`closeMercure` |
| 9 | User can view notification center page with list, filters, mark-read, click-to-navigate | VERIFIED | `NotificationsPage.vue` exists with type filter `Select`, mark-all-read Button, notification list with `@click="onClickNotification()"` using router.push for task/process_instance/organization types; empty state present |
| 10 | User can view and toggle per-event-type, per-channel preferences on the profile page | VERIFIED | `ProfilePage.vue` L259-299 has "Notification Preferences" Card with 7-event-type x 2-channel ToggleSwitch matrix; `saveNotificationPreferences()` calls `notificationStore.savePreferences()` with toast feedback |
| 11 | Mercure SSE connection is established after login and closed on logout | VERIFIED | `DashboardLayout.vue` L13-23: `watch(() => authStore.user, (user) => { if (user) notificationStore.initMercure(user.id) else notificationStore.closeMercure() }, { immediate: true })` |

**Score:** 11/11 truths verified

---

### Required Artifacts (Plan 01)

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `backend/src/Notification/Domain/Entity/Notification.php` | channel, readAt fields | VERIFIED | L19-21: `$channel`, `$readAt`, `$relatedEntityType`; `markAsRead()` sets both `isRead` and `readAt` |
| `backend/src/Notification/Domain/Entity/NotificationPreference.php` | NotificationPreference entity | VERIFIED | Full entity with userId/eventType/channel/enabled + `updateEnabled()` |
| `backend/src/Notification/Domain/ValueObject/NotificationChannel.php` | enum NotificationChannel | VERIFIED | `enum NotificationChannel: string` with `InApp = 'in_app'` and `Email = 'email'` |
| `backend/src/Notification/Infrastructure/Mercure/NotificationMercurePublisher.php` | /users/%s/notifications topic | VERIFIED | L23: `sprintf('/users/%s/notifications', $recipientId)` |
| `backend/src/Notification/Application/Service/NotificationDispatcher.php` | class NotificationDispatcher | VERIFIED | Full preference-check → DB save → Mercure publish → email send pipeline |
| `backend/src/Notification/Application/EventHandler/OnProcessStarted.php` | ProcessStartedEvent | VERIFIED | Handles `ProcessStartedEvent`, calls dispatcher with ProcessStarted type |
| `backend/src/Notification/Application/EventHandler/OnProcessCompleted.php` | ProcessCompletedEvent | VERIFIED | Sync-safe: uses DBAL, no email (emailTemplate: null) |
| `backend/src/Notification/Application/EventHandler/OnProcessCancelled.php` | ProcessCancelledEvent | VERIFIED | Handles `ProcessCancelledEvent`, notifies starter + canceller if different |
| `backend/src/Notification/Application/EventHandler/OnInvitationCreated.php` | InvitationCreatedEvent | VERIFIED | Checks user exists before in-app; skips email (already sent separately) |
| `backend/migrations/Version20260301200000.php` | notification_preferences | VERIFIED | Creates notification_preferences table + unique index + 3 new columns on notifications |

### Required Artifacts (Plan 02)

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `frontend/src/modules/notifications/stores/notification.store.ts` | EventSource | VERIFIED | L14: `let eventSource: EventSource | null = null`; `initMercure`/`closeMercure` functions fully implemented |
| `frontend/src/modules/notifications/pages/NotificationsPage.vue` | NotificationsPage | VERIFIED | Full page: type filter, notification list, click-to-navigate, mark-all-read, empty state |
| `frontend/src/modules/notifications/types/notification.types.ts` | NotificationPreferences | VERIFIED | `NotificationPreferences` interface, 8 NotificationType values, `relatedEntityType`/`channel`/`readAt` on DTO |
| `frontend/src/modules/notifications/api/notification.api.ts` | getPreferences | VERIFIED | `getPreferences()` GET and `savePreferences()` PUT implemented; `list()` accepts optional type param |
| `frontend/src/modules/auth/pages/ProfilePage.vue` | preferences | VERIFIED | Preferences section at L259-299 with ToggleSwitch matrix for 7 event types x 2 channels |
| `frontend/src/router/index.ts` | notifications | VERIFIED | Route `{ path: 'notifications', name: 'notifications', component: NotificationsPage }` at L35-37 |

---

### Key Link Verification (Plan 01)

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `OnTaskAssigned.php` | NotificationDispatcher | constructor injection | WIRED | L9: `use NotificationDispatcher`; L19: injected; L38: `$this->notificationDispatcher->dispatch([...])` |
| `NotificationDispatcher.php` | NotificationMercurePublisher | publishNotification call | WIRED | L64: `$this->mercurePublisher->publishNotification($recipientId, ...)` |
| `NotificationDispatcher.php` | NotificationMailerInterface | email send when preference enabled | WIRED | L74-85: `if (... isEnabled(..., 'email')) { $this->notificationMailer->send(...) }` |
| `NotificationController.php` | GetPreferencesQuery + SavePreferencesCommand | preferences endpoints | WIRED | L72-94: GET dispatches `GetPreferencesQuery`, PUT dispatches `SavePreferencesCommand` |

### Key Link Verification (Plan 02)

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `notification.store.ts` | Mercure hub | EventSource on /users/{userId}/notifications | WIRED | L55: `eventSource = new EventSource(\`${mercureUrl}?topic=${topic}\`)` where topic = `/users/${userId}/notifications` |
| `NotificationsPage.vue` | router | router.push for relatedEntityType | WIRED | L49-56: `router.push(...)` for task/process_instance/organization using `relatedEntityType` switch |
| `DashboardLayout.vue` | notification.store.ts | initMercure/closeMercure on auth state | WIRED | L13-23: `watch(() => authStore.user, (user) => { if (user) notificationStore.initMercure(user.id) else notificationStore.closeMercure() }, { immediate: true })` |
| `ProfilePage.vue` | notification.store.ts | savePreferences | WIRED | L95: `await notificationStore.savePreferences(localPreferences.value)` |

---

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| NOTF-01 | 09-01 | Notification entity stored in DB with type, channel, recipient, payload, status, readAt | SATISFIED | Notification entity has all fields; Notification.orm.xml maps channel/readAt/relatedEntityType; migration adds columns |
| NOTF-02 | 09-01 + 09-02 | In-app notifications delivered real-time via Mercure SSE (per-user topics) | SATISFIED | NotificationMercurePublisher publishes to /users/{id}/notifications; notification.store.ts subscribes via EventSource |
| NOTF-03 | 09-01 | Email notifications sent async via Symfony Mailer + Twig templates | SATISFIED | SymfonyNotificationMailer renders Twig templates; InvitationCreatedEvent routed async; email gated by preference check (opt-in) |
| NOTF-04 | 09-01 + 09-02 | User can configure notification preferences per event type per channel | SATISFIED | NotificationPreference entity + SavePreferencesHandler + GET/PUT /preferences API; ProfilePage preference toggle matrix |
| NOTF-05 | 09-02 | Bell icon in topbar with unread count badge | SATISFIED | NotificationBell.vue imported in AppTopbar.vue L8 and rendered L57; badge binds `store.unreadCount`; polling removed |
| NOTF-06 | 09-02 | Notification center page with list, filters, mark-read, click-to-navigate | SATISFIED | NotificationsPage.vue: type filter Select, mark-all-read Button, notification list, click-to-navigate via router.push |
| NOTF-07 | 09-01 | Triggers: task assigned, task completed, process started/completed, comment added, invitation received | SATISFIED | 7 handlers: OnTaskAssigned, OnTaskStatusChanged (TaskCompleted type when status=done), OnCommentAdded, OnProcessStarted, OnProcessCompleted, OnProcessCancelled, OnInvitationCreated |

All 7 requirements satisfied. No orphaned requirements detected.

---

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `frontend/src/modules/notifications/pages/NotificationsPage.vue` | 106 | `:placeholder="t('notifications.center.filterByType')"` — i18n key used as placeholder attribute value | Info | Not an anti-pattern; this is correct PrimeVue Select usage |

No blockers or warnings found. The single "placeholder" match is the legitimate PrimeVue `:placeholder` prop attribute, not a stub indicator.

---

### Human Verification Required

#### 1. Real-time Bell Count Update via Mercure SSE

**Test:** Log in, open two browser tabs. In tab 2 create and assign a task to the user in tab 1. Without refreshing tab 1, observe the bell icon.
**Expected:** Bell badge increments by 1 within ~1 second of the assignment, without any page refresh.
**Why human:** Requires a live Mercure hub connection and real event bus dispatch — cannot verify programmatically.

#### 2. Email Delivery When Preference Enabled

**Test:** On /profile page, enable "Email" for "Task Assigned". Then assign a task to yourself. Check Mailpit at http://localhost:8026.
**Expected:** An HTML email "Task assigned: {taskTitle}" arrives in Mailpit from the configured FROM address.
**Why human:** Requires live Mailer transport + async queue worker (`bin/console messenger:consume async`) running — cannot verify programmatically.

#### 3. Click-to-Navigate from Notification Center

**Test:** On /notifications page, click a notification with type `task_assigned`. Observe the URL.
**Expected:** Browser navigates to `/organizations/{currentOrgId}/tasks/{taskId}`. If no org is currently selected, navigation does not occur (by design).
**Why human:** Requires browser navigation within an authenticated org context — cannot verify programmatically.

---

### Observations

1. **OnTaskStatusChanged still used:** The handler is kept and uses NotificationType::TaskCompleted when `newStatus === 'done'`. This means there are 8 handlers total for 7 event types, not 7. This is acceptable and intentional per the plan's backward-compatibility note.

2. **GetPreferencesHandler returns 8 types:** Because `NotificationType::cases()` includes `TaskStatusChanged` (kept for backward compat), the GET /preferences response includes 8 keys including `task_status_changed`. The frontend displays only 7 types in the preferences matrix — this is a minor UI/backend discrepancy but does not affect functionality.

3. **InvitationCreatedEvent async routing verified:** `messenger.yaml` L51 confirms `App\Organization\Domain\Event\InvitationCreatedEvent: async`. All 5 commits referenced in SUMMARY files (ecc0617, 7c61206, 3dd99b3, c367d24, 6ad51fd) are confirmed present in git log.

4. **No polling:** `NotificationBell.vue` contains no `setInterval`, `clearInterval`, or `pollInterval` — the polling replacement is complete.

---

_Verified: 2026-03-01T20:00:00Z_
_Verifier: Claude (gsd-verifier)_
