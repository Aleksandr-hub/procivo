---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: Production-Ready BPM
status: unknown
last_updated: "2026-03-06T12:02:32.307Z"
progress:
  total_phases: 14
  completed_phases: 13
  total_plans: 38
  completed_plans: 35
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-01)

**Core value:** Users can execute BPMN processes end-to-end: design → publish → start → fill task forms → choose actions → gateway routing → complete
**Current focus:** v2.0 Production-Ready BPM — Phase 6: Process Polish

## Current Position

Phase: 14 (Infrastructure & Security)
Plan: 3 of 5 (completed, 14-03: PostgreSQL backup infrastructure)
Status: Phase 14 In Progress
Last activity: 2026-03-06 — Completed 14-03: PostgreSQL backup infrastructure

Progress: [██████------] Phase 14 — 3/5 plans complete

## Performance Metrics

**Velocity:**
- Total plans completed: 14 (v1.0)
- Average duration: ~45 min (v1.0 estimate)
- Total execution time: ~10.5 hours (v1.0)

**By Phase (v1.0):**

| Phase | Plans | Status |
|-------|-------|--------|
| 1. Backend Foundation | 3/3 | Complete |
| 2. Form Schema and Assignment | 2/2 | Complete |
| 3. Completion and Claim APIs | 2/2 | Complete |
| 4. Frontend Task Integration | 5/5 | Complete |
| 5. Designer Configuration | 2/2 | Complete |

*v2.0 metrics reset — updated after each plan completion*
| Phase 06-process-polish P03 | 22 | 2 tasks | 8 files |
| Phase 06-process-polish P04 | 3 | 2 tasks | 5 files |
| Phase 06-process-polish P02 | 14 | 2 tasks | 11 files |
| Phase 06.1-process-definition-versioning P01 | 3 | 2 tasks | 7 files |
| Phase 06.1-process-definition-versioning P02 | 5 | 2 tasks | 7 files |
| Phase 07-user-profile-cicd P03 | 1 | 2 tasks | 4 files |
| Phase 07-user-profile-cicd P01 | 35 | 2 tasks | 13 files |
| Phase 07-user-profile-cicd P02 | 3 | 2 tasks | 8 files |
| Phase 08-audit-logging P01 | 11 | 2 tasks | 44 files |
| Phase 08-audit-logging P02 | 5 | 2 tasks | 8 files |
| Phase 09-notification-system P01 | 8 | 3 tasks | 41 files |
| Phase 09-notification-system P02 | 3 | 2 tasks | 10 files |
| Phase 10-dashboard P01 | 12 | 2 tasks | 8 files |
| Phase 10-dashboard P02 | 3 | 2 tasks | 9 files |
| Phase 10-dashboard P03 | 2 | 1 task | 1 file |
| Phase 10.1-board-evolution-task-board-polish-process-board P01 | 25 | 2 tasks | 9 files |
| Phase 10.1 P03 | 35 | 2 tasks | 13 files |
| Phase 10.1-board-evolution-task-board-polish-process-board P02 | 3 | 2 tasks | 4 files |
| Phase 10.1-board-evolution-task-board-polish-process-board P04 | 3 | 2 tasks | 10 files |
| Phase 11-timer-execution P01 | 3 | 2 tasks | 7 files |
| Phase 11-timer-execution P03 | 12 | 2 tasks | 6 files |
| Phase 11.1-board-drag-to-complete-fix P01 | 3 | 1 tasks | 2 files |
| Phase 11.2 P01 | 1 | 2 tasks | 6 files |
| Phase 11.3-avatar-display-extension P01 | 4 | 2 tasks | 9 files |
| Phase 11.3 P02 | 2 | 2 tasks | 7 files |
| Phase 12-super-admin-impersonation P01 | 5 | 2 tasks | 18 files |
| Phase 12-super-admin-impersonation P02 | 15 | 3 tasks | 7 files |
| Phase 13-granular-permissions-rbac P02 | 5 | 2 tasks | 15 files |
| Phase 13 P01 | 6 | 2 tasks | 19 files |
| Phase 13 P03 | 8 | 2 tasks | 25 files |
| Phase 13 P04 | 8 | 2 tasks | 18 files |
| Phase 14-infrastructure-security P03 | 3 | 2 tasks | 5 files |
| Phase 14 P01 | 4 | 2 tasks | 17 files |

## Accumulated Context

### Decisions

Key architectural constraints for v2.0 (from research):
- Phase 8 (Audit): All audit handlers must be routed to `async` transport in messenger.yaml BEFORE writing any handler
- Phase 8 (Audit): Domain events must carry actorId explicitly — Security token is null in async workers
- Phase 9 (Notifications): Mercure topics must be per-user (/users/{userId}/notifications) — never org-wide for personal notifications
- Phase 11 (Timers): workflow_scheduled_timers table is source of truth; DelayStamp is accelerator only — plugin archived
- Phase 12 (Impersonation): Custom JWT endpoint, NOT switch_user — JWT firewall is stateless
- [Phase 06-process-polish]: Clone QueryBuilder for COUNT before LIMIT/OFFSET to avoid subquery complexity in ILIKE pagination
- [Phase 06-process-polish]: PrimeVue DataTable page event is 0-indexed; translate to 1-indexed for backend API
- [Phase 06-process-polish]: nodesSnapshot is { nodes, transitions } — access via snapshot['nodes'] for node ID extraction in MigrateProcessInstancesHandler
- [Phase 06-process-polish]: Deploy button always visible in Designer — no conditional Publish/RevertToDraft pair needed since publish() works from Published state (Plan 01)
- [Phase 06.1-01]: Migration is event-sourced: ProcessInstanceMigratedEvent recorded in event store, projection updates read model — no direct SQL UPDATE in command handler
- [Phase 06.1-01]: Action_key validation checks outgoing transitions count > 0, not exact key match — avoids per-instance current version load
- [Phase 06.1-02]: VersionHistoryDrawer uses computed drawerVisible with get/set for v-model:visible — standard PrimeVue pattern for overlay components
- [Phase 07-user-profile-cicd]: CI uses npx eslint . without --fix for pure detection — lint:eslint in package.json uses --fix which would silently fix CI failures
- [Phase 07-user-profile-cicd]: lefthook ESLint runs natively on host, CS Fixer via docker compose exec -T php
- [Phase 07-01]: AvatarStorageInterface is Identity's own port — NOT re-using TaskManager FileStorageInterface (bounded context isolation)
- [Phase 07-01]: finfo_buffer() used for MIME validation — client-provided MIME type is untrusted
- [Phase 07-01]: S3AvatarStorage uses 24h presigned URL TTL (vs 1h in S3FileStorage) for better UX with avatars
- [Phase 07-01]: UserDTO.fromEntity() accepts optional ?string avatarUrl = null — all existing callers unaffected
- [Phase 07-02]: Avatar :image receives undefined (not null) — PrimeVue Avatar treats undefined correctly, falls back to :label
- [Phase 07-02]: TaskDetailSidebar shows current user's avatar only when task.assigneeId === auth.user.id — avoids premature DTO extension before Phase 8
- [Phase 08-audit-logging]: ProcessCompletedEvent NOT routed async: OnSubProcessCompleted must run synchronously for sub-process continuation
- [Phase 08-audit-logging]: pendingActorId pattern in Task entity for Symfony Workflow setStatus() actor propagation
- [Phase 08-audit-logging]: Workflow-initiated task transitions use actorId='system' (ExecuteTaskActionHandler, OnTaskNodeActivated)
- [Phase 08-02]: AuditLogTimeline entityType/entityId props are optional — omit both for org-wide activity (OrganizationDetailPage)
- [Phase 08-02]: Task detail uses TabPanel for audit; process instance and org pages use collapsed Fieldset to avoid eager API calls
- [Phase 09-01]: NotificationDispatcher as central hub: all event handlers inject dispatcher instead of direct repository — single dispatch() call covers preference check + DB + Mercure + email
- [Phase 09-01]: email channel defaults to disabled (opt-in), in_app defaults to enabled (opt-out) — no preference row = use default
- [Phase 09-01]: OnProcessCompleted uses DBAL not Doctrine — ProcessCompletedEvent is sync; DBAL fetchAssociative is lightweight
- [Phase 09-01]: OnInvitationCreated sends in_app only if user exists — invitation email already sent by InviteUserHandler separately
- [Phase 09-02]: DashboardLayout watches authStore.user with immediate:true to manage Mercure SSE lifecycle — handles page refresh + login/logout in one place
- [Phase 09-02]: NotificationsPage navigation uses currentOrgId from organization.store — notifications don't carry orgId, org context is required for task/process links
- [Phase 09-02]: getPreference/setPreference helpers with JSON deep-clone on mount — avoids reactive aliasing with nested preferences object
- [Phase 10-01]: TASK_VIEW permission reused for dashboard stats — no separate DASHBOARD_VIEW needed; dashboard shows task/process data already behind TASK_VIEW
- [Phase 10-01]: fetchAllKeyValue() returns array<string, int> directly for status aggregates — COUNT results cast to (int) in completedByDay row mapping
- [Phase 10-01]: Dashboard store fetchAll() uses Promise.all with per-fetch try/catch isolation — one widget data failure does not block other widgets
- [Phase 10-02]: Widget components receive pre-fetched data as props from DashboardPage — no per-widget API calls, all data managed by useDashboardStore
- [Phase 10-02]: CSS variable colors resolved in onMounted() via getComputedStyle — ensures Chart.js colors match PrimeVue Aura theme in both light and dark mode
- [Phase 10-02]: RecentActivityWidget delegates to AuditLogTimeline which handles its own data fetch — unlike other widgets that receive props from DashboardPage
- [Phase 10-03]: AuditLogTimeline uses props.orgId instead of orgStore.currentOrgId — component already has orgId prop, avoids extra store dependency for navigation
- [Phase 10-03]: Use <a href="#" @click.prevent> instead of <router-link> in AuditLogTimeline — programmatic navigation in script setup, consistent with NotificationsPage.vue pattern
- [Phase 10.1-board-evolution-task-board-polish-process-board]: resolveEmployeeDisplayNames returns array{name,avatarUrl} — breaking change scoped to ListTasksHandler and GetTaskHandler only
- [Phase 10.1-board-evolution-task-board-polish-process-board]: Batch DBAL fetchAllKeyValue for comment counts in ListTasksHandler, fetchOne in GetTaskHandler — no N+1
- [Phase 10.1-board-evolution-task-board-polish-process-board]: WIP warning=80%, exceeded=100%; CSS uses --p-orange-500 for warning (amber does not exist in PrimeVue Aura)
- [Phase 10.1-03]: Kahn BFS on raw nodesSnapshot arrays (not ProcessGraph) to avoid cross-module coupling in TaskManager
- [Phase 10.1-03]: Active node identified by parsing tokens JSONB in PHP — tokens is object keyed by token ID, first waiting token is active node
- [Phase 10.1-03]: Assignee names via direct DBAL identity_users query — avoids OrganizationQueryPort coupling in Wave 1 parallel execution with Plan 01
- [Phase 10.1-03]: completedByDay sparkline queries org-wide audit_log process.completed events — per-definition filtering deferred
- [Phase 10.1-02]: QuickFilterBar defineModel pattern: uses defineModel for each filter value — clean 2-way binding; swimlanes computed returns Swimlane[] — getTasksForColumnInLane cross-joins lane.tasks with column.statusMapping; URL param sync uses undefined (not empty string) to remove params — clean shareable URLs
- [Phase 10.1-board-evolution-task-board-polish-process-board]: executeAction uses POST /tasks/{taskId}/workflow-action (not /transition) — process board advances via workflow engine, preserves token semantics
- [Phase 10.1-board-evolution-task-board-polish-process-board]: ActionFormDialog receives StatusAction prop — converted WorkflowActionDTO to StatusAction shape inline in onDrop handler
- [Phase 11-01]: workflow_scheduled_timers is the source of truth; DelayStamp is the accelerator — double-fire is impossible due to atomic UPDATE WHERE fired_at IS NULL guard
- [Phase 11-01]: FireTimerHandler returns early (no throw) when process instance not found — in async context deleted instances should be silently skipped
- [Phase 11-01]: TimerScheduledEvent routed to async transport so OnTimerScheduled runs in background worker, main request cycle never blocks on RabbitMQ
- [Phase 11-01]: FireOverdueTimersCommand default mode is continuous loop (sleep 300s); --once flag for testing — no cron needed
- [Phase 11-timer-execution]: dateValue.toISOString() produces UTC ISO 8601 with Z suffix; backend DateTimeImmutable() parses this correctly regardless of timezone offset
- [Phase 11-timer-execution]: TimerNodeConfig SelectButton mode toggle pattern: timerTypeOptions array + v-if blocks per mode — reusable for future node config panels
- [Phase 11-03]: fromRow(row, timerFireAtMap=[]) enrichment pattern — optional secondary map keeps other callers (ListProcessInstancesHandler) unaffected without changes
- [Phase 11-03]: Deadline badge shown only when status==='waiting' AND fire_at present — completed/cancelled tokens never show badge
- [Phase 11.1-board-drag-to-complete-fix]: No new API method needed — existing completeTask() dispatches same ExecuteTaskActionCommand as the removed executeAction() intended to
- [Phase 11.2]: from_variable variable name shown as disabled InputText — backend convention not user-configurable
- [Phase 11.3-01]: resolveDisplayNamesWithAvatars added as separate method on UserQueryPort — preserves backward compatibility with existing resolveDisplayNames callers
- [Phase 11.3-02]: Removed useAuthStore and isCurrentUserAssignee/isCurrentUserCreator from TaskDetailSidebar — DTO fields provide avatar for any user
- [Phase 12-01]: ImpersonateUser uses query.bus (not command.bus) because it returns ImpersonationDTO — command bus dispatch() returns void
- [Phase 12-01]: JWT impersonation uses JWTEncoderInterface::encode() directly for custom 900s TTL — jwtManager->create() always uses configured 3600s TTL
- [Phase 12-01]: Chained impersonation detection via base64 JWT payload parsing in controller — no SecurityUser extension needed
- [Phase 12-02]: impersonationTrigger ref pattern for sessionStorage reactivity — Vue computed cannot track sessionStorage natively
- [Phase 12-02]: No refresh token during impersonation — 401 triggers exit instead of refresh attempt
- [Phase 12-02]: sessionStorage (not localStorage) for admin token backup — cleared on tab close for safety
- [Phase 13-02]: ProcessDefinitionAccessChecker as dedicated Presentation service — avoids cross-module repository injection in controllers
- [Phase 13-02]: Whitelist ACL model: no rows = open to all, rows = restricted; owner bypass returns null for "show all" semantics
- [Phase 13-02]: ArrayParameterType::STRING replaces deprecated Connection::PARAM_STR_ARRAY in DBAL
- [Phase 13-04]: Permission matrix uses resource rows x action columns with scope Select dropdown per cell — diff-based save via grant/revoke API
- [Phase 13-04]: Router navigation guard allows navigation when permissions not yet loaded (loaded=false) — avoids blocking first render
- [Phase 13-04]: Permission store fetch triggered by route.params.orgId watcher in DashboardLayout with immediate:true
- [Phase 13]: Migration uses VARCHAR(36) not UUID type for consistency with existing Doctrine mappings
- [Phase 13]: Hierarchical permission merge: UserOverride deny blocks immediately; allow overrides scope; Role and Department scopes use wider-wins
- [Phase 13]: Department permission tree inheritance walks parentId chain; child department explicit permission overrides parent
- [Phase 14-03]: postgres:18-alpine as base image for backup container — includes pg_dump natively, no version mismatch
- [Phase 14-03]: Daily/weekly/monthly prefix rotation based on day-of-month and day-of-week for S3 lifecycle retention
- [Phase 14-03]: Initial backup on container startup for dev/testing verification
- [Phase 14-01]: Health endpoints at /health (not /api/health) to bypass JWT firewall for load balancer probes
- [Phase 14-01]: Prometheus CollectorRegistry uses Redis adapter for metric persistence across PHP requests
- [Phase 14-01]: Grafana on port 3001 (3000 occupied by Mercure)

### Pending Todos

None.

### Roadmap Evolution

- Phase 06.1 inserted after Phase 6: Process Definition Versioning — version definitions on deploy, bind instances to versions, admin migration with compatibility validation (URGENT)

### Blockers/Concerns

- Phase 11 (Timer): Verify docker-compose.yml for rabbitmq-delayed-message-exchange plugin reference before planning — may need DLX/TTL migration

## Session Continuity

Last session: 2026-03-06
Stopped at: Completed 14-03-PLAN.md — PostgreSQL backup infrastructure
Resume file: None
Next action: Continue Phase 14 execution (plans 04-05 remaining)
