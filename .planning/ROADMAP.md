# Roadmap: Procivo — BPM Platform

## Milestones

- ✅ **v1.0 Workflow + Tasks Integration** — Phases 1-5 (shipped 2026-03-01)
- 🚧 **v2.0 Production-Ready BPM** — Phases 6-12 (in progress)

## Phases

<details>
<summary>✅ v1.0 Workflow + Tasks Integration (Phases 1-5) — SHIPPED 2026-03-01</summary>

- [x] Phase 1: Backend Foundation (3/3 plans) — completed 2026-02-28
- [x] Phase 2: Form Schema and Assignment (2/2 plans) — completed 2026-02-28
- [x] Phase 3: Completion and Claim APIs (2/2 plans) — completed 2026-02-28
- [x] Phase 4: Frontend Task Integration (5/5 plans) — completed 2026-02-28
- [x] Phase 5: Designer Configuration (2/2 plans) — completed 2026-03-01

Full details: [milestones/v1.0-ROADMAP.md](milestones/v1.0-ROADMAP.md)

</details>

### 🚧 v2.0 Production-Ready BPM (In Progress)

**Milestone Goal:** Make the platform production-ready with notifications, audit trail, dashboard, user profiles, timer execution, and CI/CD — so small teams can actually use it.

- [ ] **Phase 6: Process Polish** — Fix v1.0 tech debt: schema snapshot, FormSchemaBuilder dedup, from_variable, process cancel, filters, UI alignment
- [ ] **Phase 7: User Profile + CI/CD** — Avatar upload to S3, profile editing, password change, CI pipeline, pre-commit hooks, developer setup docs
- [ ] **Phase 8: Audit Logging** — AuditLog module with actorId propagation, async event consumers, REST API, activity timeline UI
- [ ] **Phase 9: Notification System** — Mercure per-user SSE, async email, notification preferences, bell icon, notification center
- [ ] **Phase 10: Dashboard** — My Tasks widget, Active Processes widget, charts, activity feed from audit log
- [ ] **Phase 11: Timer Execution** — Duration + date timer node execution with persistent fallback table, Designer config, overdue indicators
- [ ] **Phase 12: Super Admin Impersonation** — Custom JWT impersonation endpoint, impersonation banner, audit trail for admin actions

## Phase Details

### Phase 6: Process Polish
**Goal**: v1.0 tech debt is eliminated and the BPM loop is complete with cancel, search, and correct schema handling
**Depends on**: Phase 5 (v1.0 complete)
**Requirements**: PLSH-01, PLSH-02, PLSH-03, PLSH-04, PLSH-05, PLSH-06
**Success Criteria** (what must be TRUE):
  1. Task detail page reads formSchema from Task.formSchema snapshot, not from live workflow context on every request
  2. FormSchemaBuilder is used in exactly one place — both task creation and task query go through the same builder
  3. User can select "from variable" assignment strategy in the designer and the backend AssignmentStrategy enum validates it
  4. User can cancel a running process instance from the ProcessInstanceDetailPage and the token stops advancing
  5. User can filter and search the process instance list by status and name, with paginated results
  6. Task detail page visual layout matches design intent — correct spacing, card structure, and field alignment
**Plans**: TBD

### Phase 7: User Profile + CI/CD
**Goal**: Users have a profile with avatar visible across the platform, and developers have automated quality gates from day one
**Depends on**: Phase 6
**Requirements**: PROF-01, PROF-02, PROF-03, PROF-04, PROF-05, ADMN-03, ADMN-04, ADMN-05
**Success Criteria** (what must be TRUE):
  1. User can view and edit first name, last name, and email on a dedicated profile page
  2. User can upload an avatar image; the server validates MIME type via finfo_buffer() and enforces 5MB max; avatar appears in topbar, comments, employee lists, and task assignments
  3. GET /api/v1/auth/me returns full profile including avatar URL with a 24-hour presigned S3 link
  4. User can change their password from the profile page using the current + new password form
  5. GitHub Actions CI pipeline runs CS Fixer, PHPStan, PHPUnit, frontend type-check, and ESLint on every push
  6. Pre-commit hooks block commits that fail CS Fixer (PHP) or ESLint (TypeScript/Vue) on staged files
  7. .env.example and README contain sufficient instructions for a new developer to run the project locally
**Plans**: TBD

### Phase 8: Audit Logging
**Goal**: Every significant system event is recorded asynchronously with full actor attribution, queryable via API and visible on detail pages
**Depends on**: Phase 7 (avatar URL on User entity, needed for actor display in audit UI)
**Requirements**: AUDT-01, AUDT-02, AUDT-03, AUDT-04
**Success Criteria** (what must be TRUE):
  1. Domain events dispatched by async Messenger workers carry actorId — no audit handler ever calls Security::getUser()
  2. AuditLog entries appear in the database for task lifecycle events, process lifecycle events, and auth events without any action on the business transaction
  3. User can query GET /api/v1/audit-log with filters for entity type, actor, and date range and receive paginated results
  4. Task detail, process instance detail, and organization detail pages each show an activity timeline built from audit log entries
**Plans**: TBD

### Phase 9: Notification System
**Goal**: Users receive real-time in-app and email notifications for relevant events, with control over what they receive
**Depends on**: Phase 8 (audit context available; Mercure module and Mailer already exist)
**Requirements**: NOTF-01, NOTF-02, NOTF-03, NOTF-04, NOTF-05, NOTF-06, NOTF-07
**Success Criteria** (what must be TRUE):
  1. Notification entity is persisted in the database with type, channel, recipient, payload, status, and readAt fields
  2. When a task is assigned, the assignee receives a real-time bell notification via Mercure SSE on their personal topic /users/{userId}/notifications — no other user sees it
  3. Email notifications are delivered asynchronously via RabbitMQ + Symfony Mailer using Twig templates; email is opt-in by default
  4. User can configure per-event-type, per-channel notification preferences from their profile page
  5. Bell icon in the topbar shows an unread count badge that updates in real-time via Mercure
  6. User can open a notification center page, filter by type, mark notifications as read, and click to navigate to the referenced entity
  7. Notifications fire for all seven trigger events: task assigned, task completed, process started, process completed, comment added, invitation received, and process cancelled
**Plans**: TBD

### Phase 10: Dashboard
**Goal**: Users see a meaningful home screen with their task workload, active processes, completion trends, and recent activity on login
**Depends on**: Phase 8 (audit_log table for activity feed), Phase 9 (unread notification count widget)
**Requirements**: DASH-01, DASH-02, DASH-03, DASH-04
**Success Criteria** (what must be TRUE):
  1. My Tasks widget shows overdue, due today, and upcoming tasks as clickable cards that navigate to task detail
  2. Active Processes widget lists processes where the user is a participant with current status badges
  3. Dashboard charts render: tasks by status (donut), tasks completed over time (line), and process completion rate (bar) — all scoped to the user's organization
  4. Recent activity feed shows the last 20 audit log entries for objects in the user's organization with clickable entity links
**Plans**: TBD

### Phase 11: Timer Execution
**Goal**: Process instances advance automatically when Timer nodes fire, with a persistent fallback that survives RabbitMQ restarts
**Depends on**: Phase 7 (CI/CD in place to catch regressions; timer work is otherwise independent)
**Requirements**: TIMR-01, TIMR-02, TIMR-03, TIMR-04
**Success Criteria** (what must be TRUE):
  1. A process with a duration Timer node (e.g., PT1H) automatically advances its token after the configured delay, even if RabbitMQ was restarted during that delay — the fallback fires within 5 minutes
  2. A process with a date Timer node waits until the configured absolute datetime, then advances — verified by integration test
  3. Designer timer node configuration panel lets the user set duration (ISO 8601 picker) or fixed date, and the configuration is correctly serialized for the engine
  4. Running process instances with passed deadlines display an overdue indicator on the ProcessInstanceDetailPage
**Plans**: TBD

### Phase 12: Super Admin Impersonation
**Goal**: Super admins can impersonate any user for support purposes with a full audit trail and no JWT architecture violations
**Depends on**: Phase 8 (audit log infrastructure required for impersonation events)
**Requirements**: ADMN-01, ADMN-02
**Success Criteria** (what must be TRUE):
  1. Super admin calls POST /api/v1/admin/impersonate/{userId} and receives a short-lived impersonation JWT with impersonated_by claim — Symfony switch_user is not used
  2. While impersonating, a persistent orange banner is visible on every page showing who is being impersonated; the exit button discards the impersonation JWT and restores the admin session
  3. audit_log contains an impersonation.started entry (with reason) and impersonation.ended entry, both with actor_id and impersonated_by fields
  4. Attempting to impersonate another super admin returns a 403 — privilege escalation is blocked
**Plans**: TBD

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Backend Foundation | v1.0 | 3/3 | Complete | 2026-02-28 |
| 2. Form Schema and Assignment | v1.0 | 2/2 | Complete | 2026-02-28 |
| 3. Completion and Claim APIs | v1.0 | 2/2 | Complete | 2026-02-28 |
| 4. Frontend Task Integration | v1.0 | 5/5 | Complete | 2026-02-28 |
| 5. Designer Configuration | v1.0 | 2/2 | Complete | 2026-03-01 |
| 6. Process Polish | v2.0 | 0/TBD | Not started | - |
| 7. User Profile + CI/CD | v2.0 | 0/TBD | Not started | - |
| 8. Audit Logging | v2.0 | 0/TBD | Not started | - |
| 9. Notification System | v2.0 | 0/TBD | Not started | - |
| 10. Dashboard | v2.0 | 0/TBD | Not started | - |
| 11. Timer Execution | v2.0 | 0/TBD | Not started | - |
| 12. Super Admin Impersonation | v2.0 | 0/TBD | Not started | - |
