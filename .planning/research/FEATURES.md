# Feature Research

**Domain:** Production-Ready BPM Platform (v2.0 milestone)
**Researched:** 2026-03-01
**Confidence:** MEDIUM-HIGH (Camunda/Flowable verified via official docs; notification/dashboard patterns from cross-referenced BPM vendor docs + OpsHub audit research; CI/CD from Symfony community sources)

---

## Research Context

Procivo v1.0 shipped a complete end-to-end BPM loop: design → publish → start → task forms → actions → XOR routing → complete. This milestone (v2.0) makes the platform production-ready for small teams by adding the seven operational features that every mature BPM platform provides: audit trail, notifications, dashboard, user profiles, timer node execution, super admin impersonation, and CI/CD. The feature landscape below evaluates each category against what platforms like Camunda 8, Flowable, IBM BPM, ProcessMaker, and BMC Digital Workplace provide at the operational layer.

---

## Feature Landscape by Category

---

### Category 1: Audit Logging

**What it is:** A tamper-evident log of every significant user action and system event, capturing who did what, when, to which object, and from where.

#### Table Stakes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Log task lifecycle events (created, claimed, completed, reassigned) | Any BPM platform since Camunda 7 logs task instance events: CREATE, UPDATE, COMPLETE, DELETE. Users expect to trace task history. | MEDIUM | Camunda logs CREATE, UPDATE, COMPLETE, DELETE, MIGRATE on task instances. Each maps to a domain event already emitted by Procivo's event bus. Wire domain events → async `WriteAuditLogHandler` → `audit_log` table. |
| Log process lifecycle events (started, completed, cancelled, error) | Process instances are the unit of work. Operations, compliance, and debugging all require process-level history. | LOW | `ProcessInstanceStarted`, `ProcessInstanceCompleted`, `ProcessInstanceFailed` events exist in the Workflow module. Consume on `event.bus` asynchronously. |
| Log user authentication events (login, logout, failed attempt) | Industry standard — SOC 2, GDPR, HIPAA all require access logs. Users expect to see who logged in when. | LOW | Symfony Security `security.interactive_login` event. Separate from domain events but same audit log table. |
| Log permission-sensitive operations (role changes, user activation/deactivation) | Admin actions with high risk. These are what compliance auditors check first. | LOW | Organization module already has `EmployeeActivated`, `RoleAssigned` events. Consume on event bus. |
| Audit log timeline UI on process detail | Showing the history of a process instance inline on the process page. Appian, IBM BPM, and ProcessMaker all provide this. | MEDIUM | Read from `audit_log` filtered by `entity_type=process_instance, entity_id={id}`. Timeline component (reuse `ProcessHistoryTimeline.vue` pattern). |
| Immutable, append-only log | Audit entries must never be modified after write. Compliance requirement. Standard in all serious platforms. | LOW | Insert-only table, no UPDATE/DELETE permissions on `audit_log`. Use `created_at` timestamp; no `updated_at`. |

#### Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Searchable/filterable audit log page (admin view) | Admins can investigate by user, date range, event type, or entity. Goes beyond basic "show log". | MEDIUM | Admin-only page with filters. Query `audit_log` with indexed columns. Not a day-one requirement but strongly expected by any team using the platform seriously. |
| Audit log on user profile (per-user activity trail) | HR/legal scenario: "Show me everything UserX did in the last 30 days." | MEDIUM | Filter audit_log by `actor_id`. Component reuse from the admin audit page. |
| Export audit log to CSV | Compliance handoff. Auditors do not log into apps to check — they want spreadsheets. | LOW | Add `?format=csv` query param to audit log API. Streams result via `BinaryFileResponse`. |
| Audit diff for form submissions (before/after values) | Show what values were submitted in each action. For compliance processes (invoice approval, HR decisions), "what was submitted" matters as much as "who submitted". | HIGH | Store `context.form_data` JSONB on the audit log entry at task completion. Then render it in the timeline. The data already flows through `CompleteTaskHandler` — capture it there. |

#### Anti-Features

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Synchronous audit writes on every request | "We want guaranteed log before response" | Adds latency to every user action. If audit DB is slow, users feel it. Worse: if audit write fails, should the user action fail too? | Async via `event.bus` on RabbitMQ. Accept eventual consistency of a few seconds. Only failure scenario: RabbitMQ dies and audit events are lost — acceptable for a pet project; for production add dead-letter queue. |
| Full HTTP request/response logging | Seems thorough | Massive storage cost. Captures passwords in query strings if misconfigured. No regulatory standard requires full HTTP log. | Log at the domain event level (business actions), not HTTP level. |
| Blockchain-based tamper-proof log | Sounds impressive | Complete over-engineering for this use case. Immutable append-only PostgreSQL table with write-restricted permissions achieves the same functional guarantee at zero complexity cost. | Insert-only table, no UPDATE permission on the log table. |

#### Audit Log Entry Schema (what each entry must contain)

Every audit entry requires: `id`, `event_type` (e.g., `task.completed`), `actor_id`, `actor_name`, `entity_type` (e.g., `task`), `entity_id`, `context` (JSONB — action taken, form data summary, old/new values), `occurred_at` (UTC), `ip_address`, `request_id` (for correlating entries in one HTTP request).

---

### Category 2: Notification System

**What it is:** Delivery of relevant events to users via in-app (bell icon + Mercure real-time) and email channels, with per-user preference control.

#### Table Stakes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Task assigned notification (in-app + email) | The most fundamental BPM notification. Camunda, Flowable, IBM BPM, and every Jira-like tool sends "you have been assigned a task." Without this, users must poll the task list. | MEDIUM | Trigger: `OnTaskNodeActivated` creates task + emits domain event. Notification handler reads `task.assigneeId`, creates `Notification` entity, publishes via Mercure, optionally sends email via Symfony Mailer. |
| Pool task available notification (in-app) | When a task becomes available for claiming, candidate users (by role/department) should be notified. Camunda Tasklist does this. | MEDIUM | Trigger: `OnTaskNodeActivated` for `by_role`/`by_department` tasks. Notify all users in the candidate group. Fan-out problem — batch or queue. |
| Task deadline approaching warning | Show visual warning when a task's due date is within N hours/days. IBM BPM provides this via configurable thresholds. | LOW | Due date stored on Task. Timer-based Symfony Scheduler job queries overdue tasks and creates notifications. Not dependent on Timer Node — this is just task due dates. |
| Bell icon with unread count | Universal UX pattern. Every enterprise app from Slack to Jira to Salesforce has this. Missing = platform feels unfinished. | LOW | `Notification` entity has `read_at` nullable column. Frontend bell queries unread count on mount + via Mercure push. |
| Mark notification as read | The inverse of the bell — users need to dismiss notifications. | LOW | `PATCH /notifications/{id}/read` endpoint. Updates `read_at`. |
| Mark all as read | Batch dismiss. All major platforms provide this. | LOW | `PATCH /notifications/read-all`. One UPDATE query with `WHERE user_id = :userId AND read_at IS NULL`. |
| Notification inbox page | Full list of past notifications with filter (unread/all). Camunda has a task portal, IBM BPM has a notification center. | MEDIUM | Paginated list from `Notification` table. Already have the module from v1.0 (Mercure module exists). Verify if persistent storage was added. |
| Email for task assignment | Users are not always in the app. Email reaches them wherever they are. IBM BPM, ProcessMaker, Flowable all support email notification for task assignment. | MEDIUM | Symfony Mailer + Twig email template. Async dispatch via `event.bus`. Store `email_sent_at` on notification or use a separate outbox. |

#### Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Per-user notification preferences | Users control which events trigger which channels. "Email me only for assignments, not for process completions." Oracle BPM allows channel selection per notification type. | MEDIUM | `NotificationPreference` entity: `user_id`, `event_type`, `channel` (in_app / email), `enabled`. Default: all on. Frontend settings page. |
| Process completed notification to initiator | Tell the person who started a process when it finishes. Intuitive but not always implemented. | LOW | `OnProcessInstanceCompleted` event → notify `process_instance.initiator_id`. |
| Task comment mention notification | @-mention in a task comment notifies the mentioned user. Familiar from Jira, GitHub, Notion. | HIGH | Parse comment text for `@username` patterns. Not in scope for this milestone — deferred. |
| Notification grouping/digest | Batch multiple notifications into one email (e.g., "You have 5 new tasks"). Reduces email noise. | HIGH | Requires scheduling logic and grouping state. Oracle BPM supports configurable digest intervals. Defer to v2.x. |

#### Anti-Features

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Real-time notification for every process event | "Push everything live" | Fan-out problem: process with 50 participants × every node transition = notification storm. Users mute everything. | Notify only the directly impacted user (assignee, initiator). Gate other events behind preferences opt-in. |
| SMS notifications | Enterprise feature request | Requires Twilio/AWS SNS integration, phone number collection, opt-in compliance (TCPA/GDPR). Cost per message. | Email + in-app covers 99% of web BPM use cases. SMS is a future premium add-on. |
| Push notifications (mobile) | Users want mobile alerts | No mobile app exists. Web push requires service workers, VAPID keys, browser permission UX. | In-app bell + email for the web-first platform. |

#### Notification Trigger Catalogue (complete list for this milestone)

| Trigger Event | Recipient | Channels | Priority |
|---------------|-----------|----------|----------|
| Task assigned (direct) | Assignee | in-app + email | P1 |
| Pool task created | All candidates in role/dept | in-app | P1 |
| Task deadline in 24h | Assignee | in-app | P2 |
| Task overdue | Assignee | in-app + email | P2 |
| Process completed | Initiator | in-app | P2 |
| Process cancelled | Initiator + current assignees | in-app | P2 |
| Comment on my task | Task assignee | in-app | P3 |

---

### Category 3: Dashboard

**What it is:** A home-screen overview of what matters to the current user — their tasks, active processes, team workload, and platform health.

#### Table Stakes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| "My Tasks" widget (count + quick list) | Every BPM platform's primary widget. Camunda Tasklist, IBM BPM Process Portal, ProcessMaker — all start with "my open tasks". | LOW | Query existing `ListTasks` endpoint filtered to current user, status != done. Count + top 5 with links to detail. |
| "My Overdue Tasks" widget | High-urgency highlight. Users need to see what's late at a glance. KissFlow, Flowable, all include overdue task counts in dashboard. | LOW | Same query with `due_date < now()`. |
| "Active Processes I Started" widget | Process initiators want to track their submissions. ProcessMaker's dashboard has "my processes" column. | LOW | Query `ProcessInstance` where `initiator_id = me` AND `status = active`. Show count + list with status. |
| "Recent Activity" feed | Chronological stream of recent actions on my objects. Common in enterprise apps (Jira, Confluence, Asana). | MEDIUM | Read from `audit_log` filtered to objects the user owns or is assigned to. Last 20 entries. No realtime needed — polling on mount is fine. |
| Team task count (if manager) | Managers need to see their team's workload. IBM BPM's performance dashboard shows team stats. | MEDIUM | Query `ListTasks` for all users in manager's department. Group by status. Bar chart or summary counts. Only visible if user has manager permission. |

#### Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Process cycle time chart | Average time per process definition to complete. The core BPM KPI — identifies slow processes. Camunda Optimize and Flowable Analytics both track this. | HIGH | Requires `completed_at - started_at` aggregation grouped by `process_definition_id`. Meaningful only with enough data. Chart.js or PrimeVue Chart component. |
| Task completion trend (7-day bar chart) | Visual pulse of team productivity. "How many tasks completed each day this week?" | MEDIUM | Aggregate `audit_log WHERE event_type = task.completed GROUP BY DATE(occurred_at)`. Simple bar chart. Impressive visually, low data complexity. |
| Processes by status (pie/donut chart) | Shows how many processes are active vs completed vs failed across the org. BPM health overview. | LOW | Aggregate `ProcessInstance` by `status`. PrimeVue Chart component. One query. |
| Bottleneck detection (longest-waiting tasks) | Table of tasks that have been open longest. Process designers use this to identify poorly designed nodes. | MEDIUM | Query tasks by `(now() - created_at)` descending. Show top 10 with process name + node name. High analytical value. |

#### Anti-Features

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Fully customizable drag-and-drop widget layout | "Let users arrange their dashboard" | Widget layout state management (per-user, persisted in DB), drag-and-drop library, responsive grid complexity. Takes weeks for questionable value. | Fixed layout with role-aware sections. Show manager widgets only to managers. Simpler, usable, fast to ship. |
| Real-time dashboard auto-refresh | "Update counts live without reload" | Mercure subscriptions for aggregate counts require separate Mercure topics per metric or a polling loop. Complexity for marginal UX gain on a dashboard. | Refresh button + auto-refresh every 60 seconds via `setInterval`. 99% of users are fine with 1-minute staleness. |
| 50+ configurable widget types | Kissflow and Quixy advertise this | Widget framework = a product in itself. Building 50 widget types is not 1 milestone's work. | Build 5-7 high-value fixed widgets. Add more per milestone. |

---

### Category 4: User Profile + Avatar

**What it is:** A profile page where users manage their personal information, upload a photo, and configure account settings.

#### Table Stakes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Display name, email, position shown on profile page | Basic identity presentation. Every enterprise SaaS has a profile page. Users expect to see their own info. | LOW | Read from existing `Employee` + `User` entities. No new data — just a presentation layer. |
| Avatar upload (S3) | Faces humanize the platform. Jira, Asana, Notion, SAP — all display user avatars on tasks and comments. Missing = platform feels impersonal. | MEDIUM | S3FileStorage already exists (LocalStack in dev, AWS in prod). Upload endpoint: `POST /profile/avatar` → resize to 200x200 → store in S3 → save URL on User entity. Return presigned or public URL. |
| Avatar displayed on task assignee field | Task cards, task detail, comments, assignment indicators all show the face of the person. This is where the avatar has 90% of its value. | LOW | `TaskDTO` and `CommentDTO` already have `assigneeId`. Extend DTO to include `assignee_avatar_url`. Frontend `<Avatar>` PrimeVue component already exists in the library. |
| Avatar displayed in top navigation (current user) | Confirmation that the profile was set. Standard position for "my account" avatar across all web apps. | LOW | PrimeVue `Avatar` in the top nav bar. Read from auth store. |
| Change password form | Security hygiene. Users expect to manage their own credentials. | LOW | Existing `ChangePassword` command likely already exists (or is trivial to add). Hash with `PasswordHasher`. |
| Profile page links to "My Tasks" and "My Processes" | Profile pages in BPM tools serve as a personal portal. BMC Digital Workplace links profile to workload view. | LOW | Links to existing filtered views. Zero backend work. |

#### Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Notification preferences on profile page | User controls their own notification channel settings. Oracle BPM, BMC DWP both put notification prefs on profile. | MEDIUM | Links to the `NotificationPreference` entity from Category 2. Form with toggles per event type + channel. |
| "My Activity" log tab on profile | Show the user their own recent actions. Auditors love it; users feel accountable. | LOW | Read from `audit_log WHERE actor_id = me`. Reuse the audit timeline component. |
| Locale/language preference per user | Users in a multilingual team choose their own language. i18n already supports uk/en. | LOW | Add `locale` field to `User` entity. Store selected language. Frontend reads from profile and sets `i18n.locale`. |

#### Anti-Features

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Free-form bio / social links on profile | "Make it feel like LinkedIn" | BPM platform is not a social network. Employee bio data already lives in the Organization module. Duplicating it on User profile creates sync problems. | Pull bio/position from `Employee` entity read-only. Profile page = system account settings, not social profile. |
| Avatar cropper/editor in-browser | "Let users crop before upload" | Cropper libraries (Cropper.js) add frontend complexity, mobile edge cases, and testing surface. | Server-side resize to 200x200 square on upload. Simple, consistent, sufficient. |
| Cover photo / banner | LinkedIn/Facebook aesthetic | Adds S3 storage, layout complexity, and a second upload UI — for zero BPM value. | Stick to avatar only. |

---

### Category 5: Timer Node Execution

**What it is:** BPMN Timer Intermediate Catch Events that pause process token execution for a specified duration or until a specific date, then automatically continue.

#### Table Stakes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Duration timer (ISO 8601 `PT10M`, `P1D`, etc.) | BPMN 2.0 spec requirement. Duration timers are the most common timer type — "wait 2 days, then send reminder". Camunda, Flowable, Oracle BPM all implement this as table stakes. | HIGH | `Timer` node type already exists in the process graph. Needs: when token reaches Timer node, calculate `fire_at = now() + duration`, store `PendingTimer` record, dispatch a delayed message via RabbitMQ (Dead Letter Exchange or `rabbitmq-delayed-message-exchange` plugin), consume on `TimerFiredHandler` → advance token. |
| Date timer (`2026-12-31T09:00:00Z`) | Exact-date process triggers. Less common than duration but standard in BPMN 2.0. Useful for deadline-based processes. | HIGH | Same infrastructure as duration timer. `fire_at = parsed ISO 8601 date`. Delay = `fire_at - now()`. RabbitMQ DLX delay in milliseconds. |
| Timer process variables expressions (`P${variables.review_days}D`) | Dynamic timers where duration is derived from process variables. Camunda 8 supports temporal expressions on timer events. This is what makes timers actually useful in BPM (not just hardcoded). | HIGH | Evaluate duration expression against `ProcessInstance.variables` at timer node activation time. Symfony ExpressionLanguage already handles variable substitution in the project. |
| Deadline display on active process tasks | When a timer node is pending, users want to see "process resumes on [date]". Operational transparency. | MEDIUM | Expose `PendingTimer.fire_at` via `GetProcessInstanceGraph` query. Display on process timeline/context card. |
| Timer cancellation when process is cancelled | If a process is cancelled while a timer is pending, the timer must not fire and try to advance a dead token. | MEDIUM | On `ProcessInstanceCancelled` event, delete `PendingTimer` records for that instance. Remove or ignore the RabbitMQ message (idempotency guard: check token/instance status before advancing). |

#### Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Timer Boundary Event on Task node (deadline pattern) | A task has a deadline — if not completed in N days, the process takes an alternate path (escalate, auto-reject). Camunda calls this "Timer Boundary Event (interrupting)". Highly useful for SLA enforcement. | HIGH | Complex: requires attaching a secondary timer to a Task token, not just an intermediate catch event. The task token and the timer must race — whoever fires first wins. Out of scope for this milestone; defer to v2.x. |
| Visual pending timer indicator in Designer | Show timer node in a "waiting" state with countdown when process is live at that node. ProcessMaker and Camunda Operate do this. | MEDIUM | Read `PendingTimer.fire_at` from `GetProcessInstanceGraph`. Render a countdown badge on the Timer node in the designer canvas. |

#### Anti-Features

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Cron/cycle timers (`R5/PT10M` — repeat 5 times every 10 minutes) | For polling integrations and recurring reminders | Cycle timers require tracking iteration count, re-scheduling after each fire, and stop conditions. Much more state than a one-shot timer. | One-shot duration and date timers cover all BPM use cases in this milestone. Cycle timers (e.g., daily reminder escalation) are a separate scope item. |
| Symfony Scheduler as primary timer mechanism | "Simpler than RabbitMQ delays" | Scheduler uses cron expressions and runs in process — requires the PHP process to always be running. Does not survive restarts. Cannot schedule an event 30 days from now with millisecond precision. | RabbitMQ Dead Letter Exchange for delayed messages. Already in the stack. Messages survive broker restart. Symfony Messenger already integrated. |
| External timer service (Temporal, Quartz) | "Battle-tested timer platforms" | Adds a completely new infrastructure dependency. RabbitMQ DLX already in the stack and sufficient for this scale. | Use what's already there. RabbitMQ DLX handles durations up to ~49 days with 32-bit integer milliseconds. For longer timers, store `fire_at` in DB and poll with a daily Scheduler job as a safety net. |

#### Timer Implementation Mechanics (for roadmap clarity)

The RabbitMQ DLX pattern: message published with `x-message-ttl = milliseconds_until_fire` → message expires → routed to live queue → `TimerFiredHandler` consumes it → calls `WorkflowEngine->continueFromTimer(tokenId)`. The `PendingTimer` table stores `(id, process_instance_id, token_id, node_id, fire_at, created_at, fired_at nullable)` as the source of truth. Idempotency guard in handler: if `token.status != waiting_at_timer` or `instance.status != active`, discard the message.

---

### Category 6: Super Admin Impersonation

**What it is:** An administrator can assume the identity of any user temporarily, see the platform as that user sees it, and take actions on their behalf for support/debugging purposes.

#### Table Stakes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Impersonate any user (view as that user) | Standard admin capability in enterprise platforms. Salesforce, Jira, IBM BPM, and Symfony itself all support this. Support staff and admins need it to debug user-reported issues. | MEDIUM | Symfony has built-in `switch_user` firewall feature. Activate via `?_switch_user={username}` query param. Requires `ROLE_ALLOWED_TO_SWITCH` on the admin. Token becomes `SwitchUserToken` with original admin preserved. |
| "You are impersonating UserX" banner | UX safety requirement. The admin must always know they are impersonating. Prevents accidental actions under wrong identity. | LOW | Symfony provides `IS_IMPERSONATOR` attribute and `SwitchUserToken` to detect active impersonation. Frontend reads a flag in the JWT or from `/me` endpoint. Show persistent yellow banner. |
| Exit impersonation button | The way back. Always present while impersonating. | LOW | Symfony's `?_switch_user=_exit` exits impersonation. Button sends this request. |
| Impersonation logged in audit trail | Security requirement. The Twitter 2020 hack and Microsoft Storm-0558 both exploited impersonation without proper audit. Log `impersonation.started` and `impersonation.ended` events with admin identity + target user. | LOW | Listen to Symfony `security.switch_user` event. Write audit log entry: `actor_id=admin, entity_type=user, entity_id=target_user, event_type=impersonation.started`. |
| Impersonation restricted to ROLE_SUPER_ADMIN only | "Impersonation for any admin" is a security risk. Only the highest privilege role should impersonate. | LOW | `ROLE_ALLOWED_TO_SWITCH` assigned only via role hierarchy to `ROLE_SUPER_ADMIN`. Regular `ROLE_ADMIN` cannot impersonate. |

#### Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Impersonation reason required (reason field before starting) | Forces accountability. "Why are you impersonating this user?" Logged with the audit entry. Pathify (Collegium) recommends this. | LOW | Pre-impersonation modal asking for reason. Store reason in audit log `context.reason`. Simple form gate. |
| Prevent impersonating another super admin | Safety rule. Admins impersonating admins creates privilege escalation risk (impersonation chaining). Symfony docs and Authress knowledge base both flag this. | LOW | In the `switch_user` event listener: if target user has `ROLE_SUPER_ADMIN` and actor is not the same user, throw `AuthenticationException`. |
| Time-limited impersonation session | Impersonation expires after N minutes automatically. Enterprise security pattern for tightly controlled access. | HIGH | Requires storing impersonation start time and checking it on each request. Symfony's built-in switch_user has no TTL. Needs custom middleware. Defer to v2.x. |

#### Anti-Features

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Impersonation via JWT token substitution | "Simpler to implement" | Creating a new JWT for the target user means the admin's original identity is lost. Cannot exit gracefully. Audit trail says actions were taken by the user, not the admin. | Use Symfony's built-in `SwitchUserToken` which wraps the original token. Admin identity is always recoverable. Audit trail can read the real actor from `SwitchUserToken->getOriginalToken()`. |
| Any ROLE_ADMIN can impersonate | "Admins need full access for support" | Multiple admins impersonating users multiplies the attack surface. Twitter 2020 breach: 130 accounts compromised by any admin with impersonation access. | Restrict to ROLE_SUPER_ADMIN. In a pet project with one admin this is academic, but establishing the pattern correctly has interview value. |

---

### Category 7: CI/CD Pipeline

**What it is:** Automated quality gates that run on every commit (pre-commit hooks) and every push (GitHub Actions), preventing broken code from reaching main.

#### Table Stakes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| PHP CS Fixer in CI (code style) | Symfony project without CS enforcement = inconsistent formatting in weeks. CLAUDE.md already mandates English code. CS Fixer enforces this objectively. | LOW | Already in the project (from tech stack notes). Add to GitHub Actions `on: [push, pull_request]`. Run: `php-cs-fixer fix --dry-run --diff`. Fail CI if changes detected. |
| PHPStan level 6+ in CI (static analysis) | Catches type errors, null dereferences, missing returns before runtime. Standard in modern PHP projects. Industry standard is level 6; level 8+ for strict projects. | LOW | Already in the project. Run: `phpstan analyse --level=6`. Cache the result cache between runs. |
| PHPUnit in CI (unit + integration tests) | Automated regression detection. No CI = no confidence that existing features still work after changes. | MEDIUM | GitHub Actions with PostgreSQL service container. Run: `php bin/phpunit --testdox`. Separate jobs for unit and integration tests (integration needs DB). |
| Pre-commit hook: CS Fixer + PHPStan | Prevent style violations and type errors from even reaching CI. Faster feedback than waiting for CI run. | LOW | `git hook` or `husky`-style shell script in `.git/hooks/pre-commit`. Run CS Fixer (fix mode, not dry-run) + PHPStan on staged PHP files only. |
| GitHub Actions `on: [push, pull_request]` workflow | Runs quality checks on every push. Standard GitHub CI setup. Ensures main branch is always green. | LOW | `.github/workflows/ci.yml` with jobs: `install`, `cs-fixer`, `phpstan`, `phpunit`. Use `actions/cache` for vendor/ and PHPStan cache. |
| Docker-based CI environment | Tests run in same environment as development. "Works on my machine" becomes irrelevant. | MEDIUM | Use `docker compose run --rm php bin/phpunit` in CI, or run directly in GitHub runner with PHP 8.4 action and services (PostgreSQL, Redis). Services approach is simpler. |

#### Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Vite build check in CI (frontend) | Catches frontend compilation errors and TypeScript errors before deploy. A broken build is a broken app. | LOW | Add `npm run build` step to CI. Run `vue-tsc --noEmit` for TypeScript checking. Separate job that runs in parallel with PHP jobs. |
| Playwright e2e smoke tests in CI (on merge to main only) | End-to-end verification that critical paths still work after merges. Runs on merge to avoid slowing down every commit. | HIGH | Playwright + Docker Compose to spin up full stack. Run critical path: login → start process → complete task → verify completion. Expensive to set up but high value for interview demos. Run only on `push to main`. |
| Test coverage report (Xdebug + Coveralls/Codecov) | Shows what's tested and what's not. Good for interview portfolio. | MEDIUM | Xdebug coverage driver in CI. Upload `.clover.xml` to Codecov. Badge in README. Low maintenance once set up. |
| Composer security audit in CI | Checks for known CVEs in dependencies. `composer audit` command. Ships with Composer 2.4+. | LOW | Add `composer audit` step. 1 line. Flags known vulnerable packages. |

#### Anti-Features

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| PHPUnit as pre-commit hook | "Catch failures immediately" | Full test suite takes 30-60 seconds. Developers will bypass hooks (`--no-verify`) if they slow commits. | PHPUnit runs in CI (pre-push or on PR). Pre-commit hooks run only fast checks: CS Fixer + PHPStan on staged files (~3-5 seconds). |
| Deployment to production in CI pipeline | "Full CD, push to deploy" | This is a pet project running locally/on a single VPS. Full CD adds SSH key management, environment secrets, rollback strategy — complexity without value at current scale. | CI handles quality gates only. Manual `ssh + git pull + symfony cache:clear` for deploy. Add CD when there's a real server to deploy to. |
| Multi-stage Docker build in CI for each commit | "Build image every time" | Image builds take 2-5 minutes per commit. Caching is complex with multi-stage builds. | Build image only on merge to main (or nightly). CI jobs use PHP action + service containers — faster and simpler. |

#### Recommended CI Pipeline Stage Order

Based on research: fast → slow, cheap → expensive.

```
Job 1 (parallel): PHP Lint
Job 2 (parallel): PHP CS Fixer --dry-run
Job 3 (parallel): PHPStan level 6
Job 4 (depends on 1,2,3): PHPUnit (unit tests only — no DB)
Job 5 (depends on 4): PHPUnit (integration tests — with PostgreSQL service)
Job 6 (depends on 1,2,3): npm build + vue-tsc
Job 7 (on push to main only): Playwright e2e smoke tests
```

---

## Feature Dependencies

```
[Audit Logging]
    └──requires──> [Domain events already on event.bus — DONE]
    └──enhances──> [Notification System] (audit entries can trigger notifications)
    └──enhances──> [Dashboard] (recent activity feed reads audit_log)
    └──enhances──> [Super Admin Impersonation] (impersonation must be logged)

[Notification System]
    └──requires──> [Mercure module — DONE (v1.0)]
    └──requires──> [Symfony Mailer — DONE (in stack)]
    └──enhances──> [Timer Node Execution] (timer fire can send notification)
    └──enhances──> [Dashboard] (unread notification count widget)

[Dashboard]
    └──requires──> [Audit Logging] (recent activity feed)
    └──requires──> [Notification System] (bell widget)
    └──requires──> [ProcessInstance query APIs — DONE]
    └──requires──> [Task query APIs — DONE]

[User Profile + Avatar]
    └──requires──> [S3FileStorage — DONE]
    └──requires──> [User entity — DONE]
    └──enhances──> [Dashboard] (avatar in nav bar, avatar on task cards)
    └──enhances──> [Notification System] (avatar in notification items)

[Timer Node Execution]
    └──requires──> [RabbitMQ — DONE]
    └──requires──> [Symfony Messenger — DONE]
    └──requires──> [WorkflowEngine token advance logic — DONE]
    └──requires──> [Timer node type exists in process graph — DONE]
    └──enhances──> [Notification System] (timer fire can notify assignees of next task)

[Super Admin Impersonation]
    └──requires──> [Audit Logging] (impersonation events must be logged)
    └──requires──> [RBAC + ROLE_SUPER_ADMIN — DONE]
    └──requires──> [Symfony security.switch_user firewall feature]

[CI/CD Pipeline]
    └──requires──> [PHPUnit test suite (exists, some coverage)]
    └──requires──> [PHPStan config (exists)]
    └──requires──> [PHP CS Fixer config (exists)]
    └──independent of all other features]
```

### Dependency Notes

- **Audit Logging must come before Impersonation:** Impersonation without an audit trail is a security liability. Build audit logging first, then impersonation writes its events into the established log.
- **Notifications depend on Mercure which exists:** The Notifications module with Mercure was scaffolded in v1.0. Verify whether `Notification` entity has persistent storage (if only in-memory/Mercure only, need to add DB persistence first).
- **Dashboard depends on Audit Logging for the activity feed:** The "recent activity" widget reads from `audit_log`. Without the audit log table, the activity feed widget cannot be built.
- **Timer Node Execution is independent but enhances Notifications:** When a timer fires and the workflow advances, the resulting new Task assignment should trigger the task-assigned notification from Category 2.
- **CI/CD is fully independent:** Can be built in any phase. Recommended early because it validates the codebase continuously while other features are added.

---

## MVP Definition for v2.0

### Launch With (v2.0 — This Milestone)

The minimum to call this "production-ready":

- [ ] **Audit logging (async, domain events)** — Foundation for compliance and debugging. Enables impersonation safety and dashboard activity feed.
- [ ] **Task assigned notification (in-app + email)** — The single highest-value notification. Without it, users must poll the app.
- [ ] **Bell icon with unread count + inbox** — The visible surface for in-app notifications. Without this, notifications have no UI.
- [ ] **Dashboard: My Tasks + My Processes + Activity Feed** — Home screen gives users immediate context on login.
- [ ] **Avatar upload + display on task cards** — Humanizes the platform. S3 upload infrastructure already exists.
- [ ] **Timer node execution (duration + date)** — Core BPMN feature that was unimplemented. Required for any real-world process design that includes waiting steps.
- [ ] **Super Admin impersonation + banner + audit log** — Support capability. Required for any multi-user deployment.
- [ ] **CI/CD pipeline (GitHub Actions + pre-commit hooks)** — Quality gate automation. Non-negotiable for "production-ready" claim.

### Add After Core Validation (v2.x)

- [ ] **Notification preferences per user** — Once core notifications work, add user control.
- [ ] **Pool task available notification** — Fan-out complexity; validate notification system first.
- [ ] **Dashboard process cycle time chart** — Meaningful only after enough data accumulates.
- [ ] **Audit log admin search page** — Useful but not blocking anything.
- [ ] **Playwright e2e smoke tests in CI** — High setup cost; add after CI is stable.
- [ ] **Locale/language preference on profile** — Polish feature; add when i18n coverage is complete.

### Defer to v3.0

- [ ] **Timer Boundary Events (deadline pattern)** — Architectural complexity significantly higher than intermediate timers.
- [ ] **Notification digest/grouping** — Scheduling state + grouping logic; separate scope.
- [ ] **@-mention notifications in comments** — Parsing + notification fan-out; separate feature.
- [ ] **Time-limited impersonation session** — Custom Symfony middleware; deferred security hardening.

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Audit logging (async write, audit_log table) | HIGH | LOW | P1 |
| Task assigned notification (in-app) | HIGH | MEDIUM | P1 |
| Bell icon + unread count | HIGH | LOW | P1 |
| Email notification for task assignment | HIGH | MEDIUM | P1 |
| Dashboard: My Tasks widget | HIGH | LOW | P1 |
| Dashboard: Active Processes widget | HIGH | LOW | P1 |
| Avatar upload + S3 storage | MEDIUM | MEDIUM | P1 |
| Avatar on task cards + nav | MEDIUM | LOW | P1 |
| Timer node execution (duration) | HIGH | HIGH | P1 |
| Timer node execution (date) | MEDIUM | LOW (same infra) | P1 |
| Super Admin impersonation | MEDIUM | MEDIUM | P1 |
| Impersonation banner + exit button | HIGH | LOW | P1 |
| Impersonation audit log | HIGH | LOW | P1 |
| GitHub Actions CI pipeline | HIGH | LOW | P1 |
| Pre-commit hooks (CS Fixer + PHPStan) | MEDIUM | LOW | P1 |
| Dashboard: Recent Activity feed | MEDIUM | MEDIUM | P2 |
| Dashboard: Team workload widget (manager) | MEDIUM | MEDIUM | P2 |
| Notification preferences per user | MEDIUM | MEDIUM | P2 |
| Pool task available notification | MEDIUM | MEDIUM | P2 |
| Process completed notification to initiator | LOW | LOW | P2 |
| Audit log admin search page | MEDIUM | MEDIUM | P2 |
| Dashboard: Cycle time chart | LOW | HIGH | P3 |
| Dashboard: Task completion trend chart | LOW | MEDIUM | P3 |
| Playwright e2e in CI | MEDIUM | HIGH | P3 |
| Test coverage report (Codecov) | LOW | MEDIUM | P3 |
| Time-limited impersonation | LOW | HIGH | P3 |

**Priority key:**
- P1: Build in v2.0 milestone
- P2: Add once P1 features are validated
- P3: Future milestone consideration

---

## Competitor Feature Analysis

| Feature | Camunda 8 | IBM BPM / Flowable | Procivo v2.0 Plan |
|---------|-----------|---------------------|-------------------|
| Audit trail | Full history DB (task, process, identity link events) | Comprehensive audit log with export | async domain events → audit_log, admin page, export CSV |
| Notifications | Email via task assignment; no native in-app bell | Email + SMS + IM channels; digest intervals configurable | in-app bell (Mercure) + email (Symfony Mailer); preferences per user |
| Dashboard | Tasklist (task-focused, no charts); Camunda Operate (process monitoring) | Process Portal with team workload, cycle times | My Tasks + My Processes + Activity Feed + basic charts |
| User profile | Identity service integration; no self-service avatar | User profile with avatar; linked to org data | profile page + S3 avatar + notification prefs |
| Timer events | Duration + Date + Cycle; Timer Boundary Events on user tasks | All BPMN 2.0 timer types | Duration + Date (one-shot); Boundary Event deferred |
| Impersonation | No native; requires identity provider (Keycloak) support | Admin panel user management; no impersonation documented | Symfony switch_user + banner + audit log |
| CI/CD | External; no opinionated approach | External | GitHub Actions: CS Fixer + PHPStan + PHPUnit + frontend build |

---

## Confidence Assessment

| Claim | Confidence | Source |
|-------|------------|--------|
| Camunda timer event types (duration, date, cycle) | HIGH | Official Camunda 8 docs (timer-events page) — verified |
| RabbitMQ DLX for delayed messages via Symfony Messenger | HIGH | Official RabbitMQ delayed message exchange GitHub + Symfony Messenger docs |
| Symfony switch_user impersonation mechanism | HIGH | Official Symfony docs (security/impersonating_user) — current |
| Audit log fields (who, what, when, where, context) | HIGH | OpsHub Signal audit trail best practices + Camunda history DB schema |
| BPM notification triggers (task assigned, deadline) | MEDIUM | Camunda task lifecycle docs + IBM BPM notification config + Oracle BPM notifications docs |
| CI/CD stage ordering (CS Fixer → PHPStan → PHPUnit) | MEDIUM | Symfony community articles + PHP pre-commit practices — cross-referenced |
| Dashboard metrics (cycle time, throughput) | MEDIUM | Vegam BPM metrics + KissFlow BPM features + Camunda Optimize feature list |
| Impersonation chaining prevention (do not impersonate admins) | MEDIUM | Authress knowledge base + Trustwave blog — not from an official BPM vendor doc |

---

## Sources

- Camunda 8 Timer Events documentation: https://docs.camunda.io/docs/components/modeler/bpmn/timer-events/
- Camunda BPM History and Audit Event Log: https://www.bookstack.cn/read/camunda-docs-manual/27.md
- Symfony Security Impersonation docs: https://symfony.com/doc/current/security/impersonating_user.html
- RabbitMQ Delayed Message Exchange plugin: https://github.com/rabbitmq/rabbitmq-delayed-message-exchange
- OpsHub Signal — Audit Trail Best Practices: https://signal.opshub.me/audit-trail-best-practices/
- Splunk — Audit Logs comprehensive guide: https://www.splunk.com/en_us/blog/learn/audit-logs.html
- KissFlow BPM Platform features 2026: https://kissflow.com/workflow/bpm/top-business-process-management-system-features/
- Vegam BPM Metrics and KPIs: https://www.vegam.ai/business-process-management/metrics-and-kpis
- BMC Digital Workplace notification customization: https://docs.bmc.com/xwiki/bin/view/Service-Management/Employee-Digital-Workplace/BMC-Digital-Workplace-Advanced/dwpadv2008/Administering/Administering-BMC-Digital-Workplace/Managing-broadcasts-and-notifications-for-end-users/Customizing-email-push-and-in-app-notifications/
- Authress — user impersonation risks: https://authress.io/knowledge-base/academy/topics/user-impersonation-risks
- Trustwave — When impersonation features go bad: https://www.trustwave.com/en-us/resources/blogs/spiderlabs-blog/when-user-impersonation-features-in-applications-go-bad/
- GitHub Actions CI/CD guide 2026: https://devtoolbox.dedyn.io/blog/github-actions-cicd-complete-guide
- Symfony GitHub Actions CI/CD for Symfony: https://www.strangebuzz.com/en/blog/setting-a-ci-cd-workflow-for-a-symfony-project-thanks-to-the-github-actions
- Oracle BPM notifications: https://docs.oracle.com/middleware/1221/bpm/bpm-develop/GUID-BEACFDB4-C226-4E46-8812-61FCB362A83D.htm

---

*Feature research for: Procivo BPM Platform — Production-Ready v2.0 Milestone*
*Researched: 2026-03-01*
