# Requirements: Procivo v2.0 Production-Ready BPM

**Defined:** 2026-03-01
**Core Value:** Users can execute BPMN processes end-to-end: design → publish → start → fill task forms → choose actions → gateway routing → complete — with proper task assignment and pool task claiming.

## v2.0 Requirements

Requirements for production-ready milestone. Each maps to roadmap phases.

### Process Polish

- [x] **PLSH-01**: Frontend reads Task.formSchema snapshot instead of live schema from workflow context
- [x] **PLSH-02**: Single FormSchemaBuilder used by both task creation and task query (dedup)
- [x] **PLSH-03**: FromVariable case added to AssignmentStrategy backend enum + employee picker on start form
- [x] **PLSH-04**: User can cancel a running process instance from ProcessInstanceDetailPage
- [x] **PLSH-05**: User can filter process instance list by status, search by name, paginate results
- [x] **PLSH-06**: Task detail page UI aligned with design intent (spacing, layout polish)
- [x] **PLSH-07**: Version history API + instance migration endpoint + Designer deploy flow with version indicator

### Process Definition Versioning (Phase 06.1)

- [x] **VER-01**: Event-sourced migration — ProcessInstanceMigratedEvent updates aggregate versionId so engine uses target version for execution after migration
- [x] **VER-02**: GET /versions returns running_instance_count per version; action_key compatibility validated before migration
- [x] **VER-03**: Admin can view version history and trigger migration from Designer page via VersionHistoryDrawer with confirmation dialog

### User Profile

- [x] **PROF-01**: User can view and edit profile (firstName, lastName, email) on a dedicated profile page
- [x] **PROF-02**: User can upload avatar image to S3 with server-side validation (type, size)
- [x] **PROF-03**: User avatar displayed in topbar, comments, employee lists, and task assignments
- [x] **PROF-04**: GET /api/v1/auth/me returns full profile including avatar URL
- [x] **PROF-05**: User can change password from profile page (current + new password form, uses existing PUT /api/v1/auth/password)

### Audit Logging

- [x] **AUDT-01**: Domain events carry actorId (passed through command context for async workers)
- [x] **AUDT-02**: AuditLog entity persists event_type, actor, entity, changes JSONB, timestamp — async via event.bus
- [x] **AUDT-03**: User can view audit log via REST API with filters (entity, actor, date range)
- [x] **AUDT-04**: Activity timeline displayed on task detail, process instance detail, and organization detail pages

### Notification System

- [x] **NOTF-01**: Notification entity stored in DB with type, channel, recipient, payload, status, readAt
- [x] **NOTF-02**: In-app notifications delivered real-time via Mercure SSE (per-user topics)
- [x] **NOTF-03**: Email notifications sent async via Symfony Mailer + RabbitMQ with Twig templates
- [x] **NOTF-04**: User can configure notification preferences per event type per channel
- [x] **NOTF-05**: Bell icon in topbar with unread count badge
- [x] **NOTF-06**: Notification center page with list, filters, mark-read, click-to-navigate
- [x] **NOTF-07**: Triggers: task assigned, task completed, process started/completed, comment added, invitation received

### Dashboard

- [x] **DASH-01**: My Tasks widget showing overdue, due today, and upcoming tasks — clickable cards
- [x] **DASH-02**: Active Processes widget showing processes where user is participant with status badges
- [x] **DASH-03**: Charts: tasks by status (donut), completed over time (line), process completion rate (bar)
- [x] **DASH-04**: Recent activity feed from audit log with entity links

### Board Evolution (Phase 10.1)

- [x] **BRD-01**: Task cards display avatar badge, priority chip, label dots, due date (red overdue), comment count
- [x] **BRD-02**: User can toggle swimlanes: by Assignee, by Priority, or None
- [x] **BRD-03**: Quick Filter bar with text, assignee, labels, due date — filters persist in URL query params
- [x] **BRD-04**: WIP limit visual states: amber at 80%, red at 100%+, count badge
- [x] **BRD-05**: Process Board creation from published ProcessDefinition with topologically-ordered columns
- [x] **BRD-06**: Process Board cards show instance name, current stage, started date, active task assignee
- [x] **BRD-07**: Drag-to-complete triggers ExecuteTaskAction with ActionFormDialog for required fields
- [x] **BRD-08**: Process Board pipeline metrics: total active, throughput sparkline (14 days)

### Timer & Scheduler

- [x] **TIMR-01**: Duration timer node execution (delay N minutes/hours/days) via RabbitMQ + persistent DB fallback
- [x] **TIMR-02**: Date timer node execution (wait until specific datetime) via symfony/scheduler + DB fallback
- [x] **TIMR-03**: Timer node configuration in Workflow Designer (duration picker, date picker)
- [x] **TIMR-04**: Overdue indicators on running process instances with deadline tracking

### Admin & CI/CD

- [x] **ADMN-01**: Super Admin impersonation via custom JWT endpoint with impersonated_by claim + short TTL
- [x] **ADMN-02**: Impersonation banner in frontend showing who is being impersonated + exit button
- [x] **ADMN-03**: GitHub Actions CI pipeline: CS Fixer + PHPStan + PHPUnit + frontend type-check + lint
- [x] **ADMN-04**: Pre-commit hooks via lefthook (lint + type-check on staged files)
- [x] **ADMN-05**: .env.example + README with setup instructions


### Granular Permissions (RBAC)

- [x] **PERM-01**: Permission model supports resource-type + action granularity with hierarchical inheritance: Organization -> Department -> Role -> User
- [ ] **PERM-02**: Effective permissions computed by merging inherited permissions with user-level overrides taking highest priority
- [x] **PERM-03**: Per-definition access control — admin can restrict which departments/roles can start or view a specific process
- [x] **PERM-04**: Admin UI with permissions management page — permission matrices per role, per department, per user with immediate effect
- [x] **PERM-05**: All API endpoints enforce permissions via OrganizationAuthorizer — no endpoint relies solely on "user is in organization"
- [ ] **PERM-06**: Permission changes logged in audit trail with before/after diff

### Infrastructure & Security (Phase 14)

- [x] **INFRA-01**: PostgreSQL daily backups with S3 storage, retention policy (30d daily, 3mo weekly, 1yr monthly), restore test
- [x] **INFRA-02**: 2FA (TOTP) — enroll via QR code, two-step JWT login, backup codes, remember device 30d
- [x] **INFRA-03**: Health check endpoints (/health, /health/db, /health/redis, /health/rabbitmq) — public, no JWT
- [x] **INFRA-04**: Prometheus metrics export (request duration, error rates) + Grafana dashboards
- [ ] **INFRA-05**: Soft delete for Organization, User, ProcessDefinition, Task — Doctrine filter, admin restore within 30d
- [ ] **INFRA-06**: Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy) + NelmioCorsBundle per-env CORS

## Future Requirements

Deferred to v3.0+. Tracked but not in current roadmap.

### Directories & Custom Objects

- **DIR-01**: Directory system with configurable catalogs and dynamic fields
- **DIR-02**: Entity Passport (dynamic detail pages per Directory)
- **DIR-03**: Directory items linkable to tasks and process context

### AI Assistant

- **AI-01**: Multi-provider AI service with conversation history
- **AI-02**: Read-only tools (list entities, navigate)
- **AI-03**: Write tools with preview + confirm pattern

### Advanced Workflow

- **WFLW-01**: Parallel gateway full implementation
- **WFLW-02**: SubProcess cascading execution
- **WFLW-03**: Multi-instance tasks (one node → tasks for multiple people)
- **WFLW-04**: Process versioning migration (live instances)

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Mobile app | Web-first, mobile later (v5.0+) |
| Drag-and-drop dashboard widgets | Anti-feature: fixed layout is simpler and sufficient |
| Cycle/recurring timers | Duration + date timers cover v2.0 needs |
| Boundary event timers | Requires significant engine changes, defer to v3.0+ |
| Notification digest/batching | Nice to have, not table stakes for v2.0 |
| OAuth2 login (Google, GitHub) | Deferred to v4.0 |
| Elasticsearch full-text search | Deferred to v4.0 |
| Dark mode | PrimeVue theming deferred |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| PLSH-01 | Phase 11.2 | Complete |
| PLSH-02 | Phase 11.2 | Complete |
| PLSH-03 | Phase 11.2 | Complete |
| PLSH-04 | Phase 6 | Complete |
| PLSH-05 | Phase 6 | Complete |
| PLSH-06 | Phase 6 | Complete |
| PLSH-07 | Phase 6 | Complete |
| VER-01 | Phase 6.1 | Complete |
| VER-02 | Phase 6.1 | Complete |
| VER-03 | Phase 6.1 | Complete |
| PROF-01 | Phase 7 | Complete |
| PROF-02 | Phase 7 | Complete |
| PROF-03 | Phase 11.3 | Complete |
| PROF-04 | Phase 7 | Complete |
| PROF-05 | Phase 7 | Complete |
| AUDT-01 | Phase 8 | Complete |
| AUDT-02 | Phase 8 | Complete |
| AUDT-03 | Phase 8 | Complete |
| AUDT-04 | Phase 8 | Complete |
| NOTF-01 | Phase 9 | Complete |
| NOTF-02 | Phase 9 | Complete |
| NOTF-03 | Phase 9 | Complete |
| NOTF-04 | Phase 9 | Complete |
| NOTF-05 | Phase 9 | Complete |
| NOTF-06 | Phase 9 | Complete |
| NOTF-07 | Phase 9 | Complete |
| DASH-01 | Phase 10 | Complete |
| DASH-02 | Phase 10 | Complete |
| DASH-03 | Phase 10 | Complete |
| DASH-04 | Phase 10 | Complete |
| BRD-01 | Phase 10.1 | Complete |
| BRD-02 | Phase 10.1 | Complete |
| BRD-03 | Phase 10.1 | Complete |
| BRD-04 | Phase 10.1 | Complete |
| BRD-05 | Phase 10.1 | Complete |
| BRD-06 | Phase 10.1 | Complete |
| BRD-07 | Phase 11.1 | Complete |
| BRD-08 | Phase 10.1 | Complete |
| TIMR-01 | Phase 11 | Complete |
| TIMR-02 | Phase 11 | Complete |
| TIMR-03 | Phase 11 | Complete |
| TIMR-04 | Phase 11 | Complete |
| ADMN-01 | Phase 12 | Complete |
| ADMN-02 | Phase 12 | Complete |
| ADMN-03 | Phase 7 | Complete |
| ADMN-04 | Phase 7 | Complete |
| ADMN-05 | Phase 7 | Complete |
| PERM-01 | Phase 13 | Planned |
| PERM-02 | Phase 13 | Planned |
| PERM-03 | Phase 13 | Planned |
| PERM-04 | Phase 13 | Planned |
| PERM-05 | Phase 13 | Planned |
| PERM-06 | Phase 13 | Planned |
| INFRA-01 | Phase 14 | Planned |
| INFRA-02 | Phase 14 | Planned |
| INFRA-03 | Phase 14 | Planned |
| INFRA-04 | Phase 14 | Planned |
| INFRA-05 | Phase 14 | Planned |
| INFRA-06 | Phase 14 | Planned |

**Coverage:**
- v2.0 requirements: 53 total
- Satisfied: 40 (checkboxes [x])
- Pending: 7 (PLSH-01, PLSH-02, PLSH-03, BRD-07, PROF-03, ADMN-01, ADMN-02)
- Unmapped: 0

---
*Requirements defined: 2026-03-01*
*Last updated: 2026-03-06 — Phase 14 INFRA requirements added*
