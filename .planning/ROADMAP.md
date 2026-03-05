# Roadmap: Procivo — BPM Platform

## Milestones

- ✅ **v1.0 Workflow + Tasks Integration** — Phases 1-5 (shipped 2026-03-01)
- 🚧 **v2.0 Production-Ready BPM** — Phases 6-13 (in progress)
- 📋 **v3.0 Configurable Platform + AI** — Module toggling, Directories, AI Assistant, Import/Export
- 📋 **v4.0 Integrations + Growth** — Ukrainian integrations, Reports, Search, Chat, Calendar, PWA
- 📋 **v5.0 Enterprise & Scale** — SSO, Self-hosted, Billing, Compliance, GraphQL, Microservices

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

- [x] **Phase 6: Process Polish** — Fix v1.0 tech debt: schema snapshot, FormSchemaBuilder dedup, from_variable, process cancel, filters, UI alignment (completed 2026-03-01)
- [x] **Phase 7: User Profile + CI/CD** — Avatar upload to S3, profile editing, password change, CI pipeline, pre-commit hooks, developer setup docs (completed 2026-03-01)
- [x] **Phase 8: Audit Logging** — AuditLog module with actorId propagation, async event consumers, REST API, activity timeline UI (completed 2026-03-01)
- [x] **Phase 9: Notification System** — Mercure per-user SSE, async email, notification preferences, bell icon, notification center (completed 2026-03-01)
- [ ] **Phase 10: Dashboard** — My Tasks widget, Active Processes widget, charts, activity feed from audit log
- [ ] **Phase 10.1: Board Evolution** — Task Board polish (swimlanes, filters, rich cards) + Process Board (BPMN stage columns, drag-to-complete, pipeline view)
- [ ] **Phase 11: Timer Execution** — Duration + date timer node execution with persistent fallback table, Designer config, overdue indicators
- [ ] **Phase 12: Super Admin Impersonation** — Custom JWT impersonation endpoint, impersonation banner, audit trail for admin actions
- [ ] **Phase 13: Granular Permissions (RBAC)** — Per-department, per-role, per-user, per-process permissions with flexible admin UI and permission inheritance
- [ ] **Phase 14: Infrastructure & Security** — 2FA, DB backups, monitoring (Prometheus+Grafana), security hardening, environments pipeline, soft delete
- [ ] **Phase 15: API Documentation** — NelmioApiDocBundle OpenAPI 3.1, Swagger UI, Postman collection

## Phase Details

### Phase 6: Process Polish
**Goal**: v1.0 tech debt is eliminated and the BPM loop is complete with cancel, search, versioned process definitions, and correct schema handling
**Depends on**: Phase 5 (v1.0 complete)
**Requirements**: PLSH-01, PLSH-02, PLSH-03, PLSH-04, PLSH-05, PLSH-06, PLSH-07
**Success Criteria** (what must be TRUE):
  1. ProcessDefinition has a version number; each "deploy" (save from Designer) creates a new version; ProcessInstance is bound to a specific version — form schemas and node configs are read from the version the instance was started with, not from the latest definition
  2. Admin can migrate running process instances to a newer definition version; migration validates that existing task form data is compatible with the new schema before applying
  3. FormSchemaBuilder is used in exactly one place — both task creation and task query go through the same builder
  4. User can select "from variable" assignment strategy in the designer and the backend AssignmentStrategy enum validates it
  5. User can cancel a running process instance from the ProcessInstanceDetailPage and the token stops advancing
  6. User can filter and search the process instance list by status and name, with paginated results
  7. Task detail page visual layout matches design intent — correct spacing, card structure, and field alignment
**Plans:** 4/4 plans complete
Plans:
- [ ] 06-01-PLAN.md — Backend versioning refactor + FormSchemaBuilder dedup + snapshot serving + FromVariable enum
- [ ] 06-02-PLAN.md — Version history API + instance migration + Designer deploy flow
- [ ] 06-03-PLAN.md — Process instance search and server-side pagination
- [ ] 06-04-PLAN.md — Cancel confirmation dialog + task detail UI polish

### Phase 06.1: Process Definition Versioning — Event-Sourced Migration + Admin UI (INSERTED)

**Goal:** Migration of running process instances actually changes engine execution version (event-sourced, not read-model-only), with enhanced compatibility validation and a frontend admin UI for version history and migration
**Depends on:** Phase 6
**Requirements**: VER-01, VER-02, VER-03
**Success Criteria** (what must be TRUE):
  1. After migration, engine reconstitutes ProcessInstance with the new versionId — CompleteTaskNodeHandler loads the target version's snapshot, not the original
  2. Migration validates that active task nodes have outgoing transitions in the target version (action_key compatibility)
  3. GET /versions returns running_instance_count per version
  4. Admin can open a Version History drawer from the Designer page, see version list with running instance counts, and trigger migration with confirmation
**Plans:** 2/2 plans complete

Plans:
- [ ] 06.1-01-PLAN.md — Event-sourced migration (ProcessInstanceMigratedEvent) + action_key validation + running instance count in versions API
- [ ] 06.1-02-PLAN.md — Version History admin UI (VersionHistoryDrawer + migrate flow in Designer)

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
**Plans:** 3/3 plans complete
Plans:
- [ ] 07-01-PLAN.md — Backend profile API (UpdateProfile + UploadAvatar commands, UserDTO extension, migration)
- [ ] 07-02-PLAN.md — Frontend ProfilePage + avatar display integration (topbar, task sidebar)
- [ ] 07-03-PLAN.md — CI/CD infrastructure (GitHub Actions, lefthook, README, .env.example)

### Phase 8: Audit Logging
**Goal**: Every significant system event is recorded asynchronously with full actor attribution, queryable via API and visible on detail pages
**Depends on**: Phase 7 (avatar URL on User entity, needed for actor display in audit UI)
**Requirements**: AUDT-01, AUDT-02, AUDT-03, AUDT-04
**Success Criteria** (what must be TRUE):
  1. Domain events dispatched by async Messenger workers carry actorId — no audit handler ever calls Security::getUser()
  2. AuditLog entries appear in the database for task lifecycle events, process lifecycle events, and auth events without any action on the business transaction
  3. User can query GET /api/v1/audit-log with filters for entity type, actor, and date range and receive paginated results
  4. Task detail, process instance detail, and organization detail pages each show an activity timeline built from audit log entries
**Plans:** 2/2 plans complete
Plans:
- [ ] 08-01-PLAN.md — Backend Audit module (entity, actorId enrichment, event handlers, messenger routing, REST API)
- [ ] 08-02-PLAN.md — Frontend AuditLogTimeline component + integration into detail pages

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
**Plans:** 2/2 plans complete
Plans:
- [ ] 09-01-PLAN.md — Backend: domain model (channel/readAt/preferences), NotificationDispatcher, Mercure publisher, email mailer, 7 event handlers, preferences API
- [ ] 09-02-PLAN.md — Frontend: Mercure SSE subscription, NotificationsPage, preferences UI in ProfilePage, i18n

### Phase 10: Dashboard
**Goal**: Users see a meaningful home screen with their task workload, active processes, completion trends, and recent activity on login
**Depends on**: Phase 8 (audit_log table for activity feed), Phase 9 (unread notification count widget)
**Requirements**: DASH-01, DASH-02, DASH-03, DASH-04
**Success Criteria** (what must be TRUE):
  1. My Tasks widget shows overdue, due today, and upcoming tasks as clickable cards that navigate to task detail
  2. Active Processes widget lists processes where the user is a participant with current status badges
  3. Dashboard charts render: tasks by status (donut), tasks completed over time (line), and process completion rate (bar) — all scoped to the user's organization
  4. Recent activity feed shows the last 20 audit log entries for objects in the user's organization with clickable entity links
**Plans:** 1/2 plans executed
Plans:
- [ ] 10-01-PLAN.md — Backend dashboard stats endpoint + frontend data layer (types, API, store)
- [ ] 10-02-PLAN.md — DashboardPage with 4 widgets (MyTasks, ActiveProcesses, Charts, RecentActivity) + route + sidebar + i18n

### Phase 10.1: Board Evolution — Task Board Polish + Process Board

**Goal**: Boards become the primary daily work surface — polished Task Kanban with rich cards, swimlanes, and filters; plus a new Process Board type where columns are BPMN stage nodes and cards are live process instances flowing through the pipeline
**Depends on**: Phase 10 (Dashboard provides overview; boards provide hands-on workspace), Phase 8 (audit timeline on cards)
**Requirements**: BRD-01, BRD-02, BRD-03, BRD-04, BRD-05, BRD-06, BRD-07, BRD-08
**Success Criteria** (what must be TRUE):

*Task Board Polish:*
  1. Task cards display avatar badge of assignee, priority severity chip, label dots, due date (red when overdue), and comment count — visible without opening the task
  2. User can toggle swimlanes on a board: by Assignee (horizontal rows per person with "Unassigned" row), by Priority (Critical → Low rows), or None (flat columns)
  3. User can filter board cards with a Quick Filter bar: text search (title), assignee dropdown, label multi-select, due date range — filters persist in URL query params so links are shareable
  4. Column WIP limit violation is visually clear: column header turns amber at 80% capacity and red at 100%+, with a count badge showing current/limit

*Process Board (new board type):*
  5. User can create a Process Board by selecting an existing published ProcessDefinition — the system auto-generates columns from the definition's Task nodes in topological order (Start → task stages → End), plus a "Completed" column
  6. Each card on the Process Board represents a ProcessInstance: shows instance name, current stage highlight, started date, and assignee of the active task — clicking opens ProcessInstanceDetailPage
  7. Dragging a card from one stage column to the next triggers the active task's default action (complete with empty form data) — if the task has required form fields, a compact ActionFormDialog appears inline; if no valid transition exists, drag is rejected with a toast
  8. Process Board header shows pipeline metrics: total active instances, average time per stage (from audit log timestamps), and a mini throughput sparkline (instances completed per day over last 14 days)

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

### Phase 13: Granular Permissions (RBAC)
**Goal**: Organizations can configure fine-grained access control — who can see, create, edit, and manage tasks, processes, and organizational data — per department, per role, per user, and per process definition
**Depends on**: Phase 8 (audit logging for permission changes), Phase 12 (super admin role concept)
**Requirements**: PERM-01, PERM-02, PERM-03, PERM-04, PERM-05, PERM-06
**Success Criteria** (what must be TRUE):
  1. Permission model supports resource-type + action granularity (e.g., task:create, process:start, organization:manage) with hierarchical inheritance: Organization → Department → Role → User
  2. User's effective permissions are computed by merging inherited permissions from their department and role, with explicit user-level overrides (allow/deny) taking highest priority
  3. Process definitions have per-definition access control — admin can restrict which departments/roles can start or view a specific process
  4. Admin UI provides a permissions management page where org admins can view and edit permission matrices per role, per department, and per user with immediate effect
  5. All API endpoints enforce permissions via the existing OrganizationAuthorizer — no endpoint relies solely on "user is in organization" for access
  6. Permission changes are logged in the audit trail with before/after diff
**Plans**: TBD

### Phase 14: Infrastructure & Security
**Goal**: Production-grade infrastructure — automated backups, monitoring, security hardening, environment pipeline, soft delete for critical entities
**Depends on**: Phase 7 (CI/CD foundation), Phase 8 (audit logging for security events)
**Requirements**: INFRA-01, INFRA-02, INFRA-03, INFRA-04, INFRA-05, INFRA-06
**Success Criteria** (what must be TRUE):
  1. PostgreSQL daily backups run automatically, compressed dumps stored in S3 with retention policy (30d daily, 3mo weekly, 1yr monthly), and a monthly automated restore test passes on staging
  2. 2FA (TOTP) is available: user can enroll via QR code, login requires TOTP code, backup codes are generated, "remember device" skips 2FA for 30 days
  3. Health check endpoints (/health, /health/db, /health/redis, /health/rabbitmq) return status and are used by load balancer
  4. Prometheus metrics are exported (request duration, queue depth, error rates) and Grafana dashboards display system health and business metrics
  5. Soft delete is implemented for Organization, User, ProcessDefinition, and Task — deleted entities are hidden by default via Doctrine filter, admin can restore within 30 days
  6. Security headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options) are set on all responses; CORS is configured per environment
**Plans**: TBD

### Phase 15: API Documentation
**Goal**: All API endpoints are documented with OpenAPI 3.1 spec, browsable via Swagger UI, exportable as Postman collection
**Depends on**: Phase 14 (stable API surface after security hardening)
**Requirements**: DOCS-01, DOCS-02
**Success Criteria** (what must be TRUE):
  1. Every API endpoint has OpenAPI annotations — parameter types, response schemas, error codes, authentication requirements
  2. Swagger UI is accessible at /api/docs in dev/staging (auth-protected in production) and accurately reflects the live API
  3. Postman collection is auto-generated and downloadable from /api/docs
  4. API versioning strategy is documented with migration guide for v1 → v2 breaking changes
**Plans**: TBD

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Backend Foundation | v1.0 | 3/3 | Complete | 2026-02-28 |
| 2. Form Schema and Assignment | v1.0 | 2/2 | Complete | 2026-02-28 |
| 3. Completion and Claim APIs | v1.0 | 2/2 | Complete | 2026-02-28 |
| 4. Frontend Task Integration | v1.0 | 5/5 | Complete | 2026-02-28 |
| 5. Designer Configuration | v1.0 | 2/2 | Complete | 2026-03-01 |
| 6. Process Polish | 4/4 | Complete   | 2026-03-01 | - |
| 6.1 Process Definition Versioning | 2/2 | Complete   | 2026-03-01 | - |
| 7. User Profile + CI/CD | 3/3 | Complete   | 2026-03-01 | - |
| 8. Audit Logging | 2/2 | Complete   | 2026-03-01 | - |
| 9. Notification System | 2/2 | Complete   | 2026-03-01 | - |
| 10. Dashboard | 1/2 | In Progress|  | - |
| 10.1 Board Evolution | v2.0 | 0/TBD | Not started | - |
| 11. Timer Execution | v2.0 | 0/TBD | Not started | - |
| 12. Super Admin Impersonation | v2.0 | 0/TBD | Not started | - |
| 13. Granular Permissions (RBAC) | v2.0 | 0/TBD | Not started | - |
| 14. Infrastructure & Security | v2.0 | 0/TBD | Not started | - |
| 15. API Documentation | v2.0 | 0/TBD | Not started | - |

---

## Future Milestones (high-level, detailed planning when we get there)

### v3.0 Configurable Platform + AI

**Milestone Goal:** Universal directory system (Custom Objects), AI Assistant as configuration tool, module toggling per-org, process templates, import/export with competitor migration.

Phases:
- **Phase 16: Module Toggling & Menu Customization** — Per-org module flags, sidebar config per role, feature flags, custom landing pages
- **Phase 17: Directory System** — Dynamic catalogs with configurable fields (text, number, date, select, file, relation), hierarchical items, per-directory RBAC
- **Phase 18: Entity Passport** — Dynamic detail pages (PassportTemplate config → universal renderer with tabs, sections, field groups)
- **Phase 19: Directory-Workflow Integration** — Directory items as process context, task references, directory_item form field type
- **Phase 20: Process Templates** — 10-15 pre-built process templates (HR, Finance, IT, General), template marketplace, AI-suggested templates
- **Phase 21: AI Assistant — Read-Only** — Multi-provider (Claude/OpenAI/Gemini), org-scoped context isolation, RBAC-enforced tools, streaming via Mercure
- **Phase 22: AI Assistant — Write Tools + Modes** — Quick Mode (execute immediately) vs Design Mode (iterative clarification like GSD), Preview+Confirm pattern, usage limits per org
- **Phase 23: Import/Export + Migration** — CSV/Excel import with AI column mapping, competitor migration adapters (Creatio, 1C/BAS, Jira, Monday), MigrationWizard, rollback capability

### v4.0 Integrations + Growth

**Milestone Goal:** Integration framework with Ukrainian B2B connectors, reporting engine, full-text search, internal chat, calendar/SLA, mobile PWA.

Phases:
- **Phase 24: Integration Framework** — Webhook in/out, ConnectorInterface, API keys, OAuth2 client flow, delivery log
- **Phase 25: Ukrainian Business Integrations** — Nova Poshta, PrivatBank, Monobank, Vchasno EDI, Diia.Sign (KEP), Checkbox PRRO, 1C/BAS sync, OpenDataBot, Telegram Bot, IP telephony (Binotel)
- **Phase 26: Report Builder** — Configurable reports (chart, table, number card), ReportAccess sharing, AI report tools, PDF/Excel export
- **Phase 27: Full-Text Search** — Elasticsearch indexing (tasks, employees, directories, processes), global search bar, faceted results, RBAC-scoped
- **Phase 28: Chat & Discussions** — Task/process thread-based chat, direct messages, @mentions, file sharing, real-time via Mercure
- **Phase 29: Calendar & Timeline** — Calendar view for deadlines, Gantt for processes, SLA management, escalation rules
- **Phase 30: Mobile / PWA** — Service worker, responsive design, push notifications, camera integration, QR code scanning

### v5.0 Enterprise & Scale

**Milestone Goal:** Enterprise features for large organizations — SSO, self-hosted packaging, billing, compliance, microservices extraction.

Phases:
- **Phase 31: SSO & Advanced Auth** — SAML 2.0, OIDC, LDAP/AD sync, 2FA enforcement per org, session management, OAuth2 provider
- **Phase 32: Self-Hosted Packaging** — Helm chart, Docker Compose prod template, install wizard, upgrade path, air-gapped support, license key validation
- **Phase 33: Billing & Subscriptions** — Plan tiers (Free/Starter/Pro/Enterprise), per-module pricing, Stripe + LiqPay/Fondy, usage metering, trial period
- **Phase 34: Compliance & Data Protection** — GDPR (consent, right to erasure, data export), data retention policies, DPA template, SAF-T compliance
- **Phase 35: GraphQL API** — Schema for core entities, DataLoader, subscriptions, rate limiting
- **Phase 36: gRPC Inter-service** — Proto definitions, server/client implementation, service mesh prep
- **Phase 37: Microservices Extraction** — Notification + Search as separate services, Traefik gateway, distributed tracing
- **Phase 38: Advanced Workflow** — SubProcess node, multi-instance tasks, process mining/analytics, DMN decision tables, simulation, plugin marketplace
