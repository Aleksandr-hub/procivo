---
phase: 08-audit-logging
plan: 01
subsystem: audit
tags: [doctrine, dbal, audit-log, domain-events, actor-id, async-handlers, rabbitmq, symfony-messenger]

# Dependency graph
requires:
  - phase: 01-identity-organization
    provides: UserRegisteredEvent, PasswordChangedEvent, CurrentUserProviderInterface
  - phase: 02-task-manager
    provides: TaskManager domain events, TaskRepositoryInterface
  - phase: 03-workflow-engine
    provides: ProcessStartedEvent, ProcessCancelledEvent, ProcessCompletedEvent
provides:
  - AuditLog entity + DoctrineAuditLogRepository (append-only audit trail)
  - 12 async event handlers covering task, process, and auth lifecycle
  - ListAuditLogHandler with DBAL pagination and multi-filter support
  - GET /api/v1/organizations/{orgId}/audit-log REST endpoint
  - actorId propagation in all mutating task domain events
affects:
  - 09-notifications (async transport patterns established)
  - future audit UI (API ready for consumption)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Append-only entity with private constructor + static record() factory"
    - "pendingActorId context pattern for Symfony Workflow-triggered status changes"
    - "DBAL clone-for-count pagination in ListAuditLogHandler"
    - "Async audit handlers never inject Security — actor read from event field only"

key-files:
  created:
    - backend/src/Audit/Domain/Entity/AuditLog.php
    - backend/src/Audit/Domain/ValueObject/AuditLogId.php
    - backend/src/Audit/Domain/Repository/AuditLogRepositoryInterface.php
    - backend/src/Audit/Infrastructure/Persistence/Doctrine/Mapping/AuditLog.orm.xml
    - backend/src/Audit/Infrastructure/Persistence/Doctrine/Repository/DoctrineAuditLogRepository.php
    - backend/src/Audit/Application/DTO/AuditLogDTO.php
    - backend/src/Audit/Application/EventHandler/OnTaskCreatedAudit.php
    - backend/src/Audit/Application/EventHandler/OnTaskStatusChangedAudit.php
    - backend/src/Audit/Application/EventHandler/OnTaskAssignedAudit.php
    - backend/src/Audit/Application/EventHandler/OnTaskClaimedAudit.php
    - backend/src/Audit/Application/EventHandler/OnTaskUnclaimedAudit.php
    - backend/src/Audit/Application/EventHandler/OnTaskDeletedAudit.php
    - backend/src/Audit/Application/EventHandler/OnCommentAddedAudit.php
    - backend/src/Audit/Application/EventHandler/OnProcessStartedAudit.php
    - backend/src/Audit/Application/EventHandler/OnProcessCompletedAudit.php
    - backend/src/Audit/Application/EventHandler/OnProcessCancelledAudit.php
    - backend/src/Audit/Application/EventHandler/OnUserRegisteredAudit.php
    - backend/src/Audit/Application/EventHandler/OnPasswordChangedAudit.php
    - backend/src/Audit/Application/Query/ListAuditLog/ListAuditLogQuery.php
    - backend/src/Audit/Application/Query/ListAuditLog/ListAuditLogHandler.php
    - backend/src/Audit/Presentation/Controller/AuditLogController.php
    - backend/migrations/Version20260302100000.php
  modified:
    - backend/src/TaskManager/Domain/Event/TaskStatusChangedEvent.php (added actorId)
    - backend/src/TaskManager/Domain/Event/TaskAssignedEvent.php (added actorId)
    - backend/src/TaskManager/Domain/Event/TaskClaimedEvent.php (added actorId)
    - backend/src/TaskManager/Domain/Event/TaskUnclaimedEvent.php (added actorId)
    - backend/src/TaskManager/Domain/Event/TaskDeletedEvent.php (added actorId)
    - backend/src/TaskManager/Domain/Entity/Task.php (pendingActorId + withActorId() + updated method signatures)
    - backend/src/TaskManager/Application/Command/AssignTask/AssignTaskCommand.php (added actorId)
    - backend/src/TaskManager/Application/Command/AssignTask/AssignTaskHandler.php (pass actorId)
    - backend/src/TaskManager/Application/Command/ClaimTask/ClaimTaskCommand.php (added actorId)
    - backend/src/TaskManager/Application/Command/ClaimTask/ClaimTaskHandler.php (pass actorId)
    - backend/src/TaskManager/Application/Command/UnclaimTask/UnclaimTaskCommand.php (added actorId)
    - backend/src/TaskManager/Application/Command/UnclaimTask/UnclaimTaskHandler.php (pass actorId)
    - backend/src/TaskManager/Application/Command/DeleteTask/DeleteTaskCommand.php (added actorId)
    - backend/src/TaskManager/Application/Command/DeleteTask/DeleteTaskHandler.php (pass actorId)
    - backend/src/TaskManager/Application/Command/TransitionTask/TransitionTaskCommand.php (added actorId)
    - backend/src/TaskManager/Application/Command/TransitionTask/TransitionTaskHandler.php (withActorId before apply)
    - backend/src/TaskManager/Presentation/Controller/TaskController.php (inject CurrentUserProviderInterface, pass actorId)
    - backend/src/Workflow/Application/Command/ExecuteTaskAction/ExecuteTaskActionHandler.php (actorId='system')
    - backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php (actorId='system')
    - backend/config/packages/messenger.yaml (async routing for 8 new event types)
    - backend/config/packages/doctrine.yaml (Audit module mapping)
    - backend/config/services.yaml (Audit module DI registration)

key-decisions:
  - "ProcessCompletedEvent NOT routed to async: OnSubProcessCompleted must run synchronously to continue parent process execution — OnProcessCompletedAudit runs synchronously alongside it"
  - "pendingActorId pattern in Task entity: Symfony Workflow calls setStatus() automatically without actor context; withActorId() sets transient context before workflow->apply()"
  - "workflow-initiated task transitions use actorId='system' (ExecuteTaskActionHandler, OnTaskNodeActivated)"
  - "TaskRepositoryInterface injected in 5 audit handlers to resolve organizationId (async acceptable perf cost for audit correctness)"
  - "AuditLogId extends Shared Uuid VO following existing module patterns"

patterns-established:
  - "Append-only audit entity: static record() factory, private constructor, no update methods"
  - "Actor propagation: commands carry actorId from controller, domain events carry actorId from commands, handlers read from events — never from Security"
  - "Pending context pattern: entity stores transient actorId for framework-triggered callbacks (Symfony Workflow setStatus)"

requirements-completed:
  - AUDT-01
  - AUDT-02
  - AUDT-03

# Metrics
duration: 11min
completed: 2026-03-01
---

# Phase 08 Plan 01: Audit Module Backend Summary

**Append-only AuditLog entity with 12 async event handlers covering task/process/auth lifecycle, actorId propagation in all mutating domain events, and paginated REST API with DBAL filters**

## Performance

- **Duration:** 11 min
- **Started:** 2026-03-01T18:26:23Z
- **Completed:** 2026-03-01T18:37:41Z
- **Tasks:** 2
- **Files modified:** 44 (22 created, 22 modified)

## Accomplishments
- Created complete Audit module: AuditLog entity, AuditLogId VO, repository interface + Doctrine implementation, XML mapping with 3 indexes, AuditLogDTO with JsonSerializable
- Added actorId to 5 domain events (TaskStatusChanged, TaskAssigned, TaskClaimed, TaskUnclaimed, TaskDeleted) and updated all call chains through commands, handlers, and controllers
- Implemented pendingActorId context mechanism in Task entity for Symfony Workflow-triggered setStatus() calls
- Created 12 audit event handlers on event.bus covering full task/process/auth lifecycle with zero Security injection
- Added ListAuditLogHandler with DBAL clone-for-count pagination supporting entity_type, entity_id, actor_id, date_from, date_to filters
- Created GET /api/v1/organizations/{orgId}/audit-log endpoint with page/limit parameters
- Routed 8 new event types to async transport in messenger.yaml

## Task Commits

Each task was committed atomically:

1. **Task 1: Audit domain + infrastructure + domain event enrichment** - `397cf4a` (feat)
2. **Task 2: 12 event handlers + messenger routing + query handler + controller** - `32c6e84` (feat)

**Plan metadata:** `[to be added]` (docs: complete plan)

## Files Created/Modified

### Created (22 files)
- `backend/src/Audit/Domain/Entity/AuditLog.php` - Append-only audit entity with static record() factory
- `backend/src/Audit/Domain/ValueObject/AuditLogId.php` - UUID value object extending Shared Uuid
- `backend/src/Audit/Domain/Repository/AuditLogRepositoryInterface.php` - Single save() method interface
- `backend/src/Audit/Infrastructure/Persistence/Doctrine/Mapping/AuditLog.orm.xml` - XML mapping with 3 indexes
- `backend/src/Audit/Infrastructure/Persistence/Doctrine/Repository/DoctrineAuditLogRepository.php` - ORM persist/flush
- `backend/src/Audit/Application/DTO/AuditLogDTO.php` - DBAL fromRow() + JsonSerializable with ISO 8601 dates
- `backend/src/Audit/Application/EventHandler/On*Audit.php` (12 files) - All event handlers on event.bus
- `backend/src/Audit/Application/Query/ListAuditLog/ListAuditLogQuery.php` - Query with 7 filter parameters
- `backend/src/Audit/Application/Query/ListAuditLog/ListAuditLogHandler.php` - DBAL with clone-for-count pattern
- `backend/src/Audit/Presentation/Controller/AuditLogController.php` - GET /api/v1/organizations/{orgId}/audit-log
- `backend/migrations/Version20260302100000.php` - audit_log table with JSONB changes column

### Key Modifications
- Task domain events: `actorId` field added to 5 events
- `Task.php`: `pendingActorId` + `withActorId()` + updated assign/claim/unclaim/markDeleted signatures
- `TaskController.php`: injects `CurrentUserProviderInterface`, passes actorId to all mutating commands
- `ExecuteTaskActionHandler.php` + `OnTaskNodeActivated.php`: actorId='system' for workflow-triggered transitions
- `messenger.yaml`: 8 new async routes added
- `doctrine.yaml` + `services.yaml`: Audit module registered

## Decisions Made

1. **ProcessCompletedEvent stays sync:** `OnSubProcessCompleted` runs synchronously on the same event — routing to async would break sub-process continuation. `OnProcessCompletedAudit` accepts sync execution for this one event.

2. **pendingActorId pattern in Task entity:** Symfony Workflow calls `setStatus()` via marking store without actor context. Solution: `withActorId(actorId)` sets a transient field before `$workflow->apply()`, consumed and cleared in `setStatus()`. Falls back to `'system'` if not set.

3. **workflow-initiated transitions use actorId='system':** `ExecuteTaskActionHandler` and `OnTaskNodeActivated` pass `actorId: 'system'` — these are framework/engine-initiated, not user-triggered.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical] Added Audit module to services.yaml and doctrine.yaml**
- **Found during:** Task 2 verification
- **Issue:** `debug:messenger` showed no audit handlers — Symfony DI didn't scan `src/Audit/` namespace
- **Fix:** Added `App\Audit\:` service resource scan and `AuditLogRepositoryInterface` alias in services.yaml; added `Audit:` mapping entry in doctrine.yaml
- **Files modified:** `backend/config/services.yaml`, `backend/config/packages/doctrine.yaml`
- **Verification:** `debug:messenger` confirms all 12 audit handlers registered on event.bus
- **Committed in:** `32c6e84` (Task 2 commit)

**2. [Rule 1 - Bug] ProcessCompletedEvent not routed async (architectural consideration, not Rule 4)**
- **Found during:** Task 2 — examining ProcessCompletedEvent usages
- **Issue:** Routing ProcessCompletedEvent to async would break `OnSubProcessCompleted` which continues parent process execution synchronously
- **Fix:** Left ProcessCompletedEvent unrouted (sync); `OnProcessCompletedAudit` runs sync alongside `OnSubProcessCompleted`. Added explanatory comment in messenger.yaml.
- **Files modified:** `backend/config/packages/messenger.yaml`, `backend/src/Audit/Application/EventHandler/OnProcessCompletedAudit.php`
- **Committed in:** `32c6e84` (Task 2 commit)

---

**Total deviations:** 2 auto-fixed (1 missing critical registration, 1 deliberate routing decision)
**Impact on plan:** Both fixes necessary for correct operation. No scope creep.

## Issues Encountered

None beyond the deviations documented above.

## Next Phase Readiness
- Audit backend complete — all AUDT-01/02/03 requirements satisfied
- `GET /api/v1/organizations/{orgId}/audit-log` ready for frontend consumption
- actorId propagation complete — handlers are async-safe and Security-independent
- ProcessCompletedEvent audit is sync — acceptable for MVP (can be improved with organizationId enrichment in event if needed)

## Self-Check: PASSED

All key files verified present. Both task commits exist. All must_have artifacts confirmed.

---
*Phase: 08-audit-logging*
*Completed: 2026-03-01*
