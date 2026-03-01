---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: Production-Ready BPM
status: unknown
last_updated: "2026-03-01T18:39:39.853Z"
progress:
  total_phases: 4
  completed_phases: 3
  total_plans: 11
  completed_plans: 10
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-01)

**Core value:** Users can execute BPMN processes end-to-end: design → publish → start → fill task forms → choose actions → gateway routing → complete
**Current focus:** v2.0 Production-Ready BPM — Phase 6: Process Polish

## Current Position

Phase: 08 (Audit Logging) — In Progress
Plan: 1 of 1 (completed)
Status: Plan 01 complete
Last activity: 2026-03-01 — Completed 08-01: Audit module backend with 12 async event handlers, actorId propagation, and REST API

Progress: [████████░░] Phase 08 plan 01 complete

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

### Pending Todos

None.

### Roadmap Evolution

- Phase 06.1 inserted after Phase 6: Process Definition Versioning — version definitions on deploy, bind instances to versions, admin migration with compatibility validation (URGENT)

### Blockers/Concerns

- Phase 11 (Timer): Verify docker-compose.yml for rabbitmq-delayed-message-exchange plugin reference before planning — may need DLX/TTL migration

## Session Continuity

Last session: 2026-03-01
Stopped at: Completed 08-01-PLAN.md — Audit module backend with 12 async event handlers and REST API
Resume file: None
Next action: Execute Phase 07 Plans 03-04 (CI/CD remaining plans)
