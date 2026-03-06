---
phase: 13-granular-permissions-rbac
plan: 02
subsystem: auth
tags: [acl, rbac, workflow, process-definition, access-control, doctrine]

requires:
  - phase: 01-foundation
    provides: Workflow module with ProcessDefinition entity and controllers
provides:
  - ProcessDefinitionAccess entity with whitelist ACL model
  - Per-definition access enforcement on list and start endpoints
  - REST API endpoints for managing process definition access rules
  - ProcessDefinitionAccessChecker service for centralized ACL resolution
affects: [13-granular-permissions-rbac, frontend-workflow]

tech-stack:
  added: []
  patterns: [whitelist-acl, owner-bypass, access-checker-service]

key-files:
  created:
    - backend/src/Workflow/Domain/Entity/ProcessDefinitionAccess.php
    - backend/src/Workflow/Domain/ValueObject/AccessType.php
    - backend/src/Workflow/Domain/ValueObject/ProcessDefinitionAccessId.php
    - backend/src/Workflow/Domain/Repository/ProcessDefinitionAccessRepositoryInterface.php
    - backend/src/Workflow/Infrastructure/Repository/DoctrineProcessDefinitionAccessRepository.php
    - backend/src/Workflow/Infrastructure/Persistence/Doctrine/Mapping/ProcessDefinitionAccess.orm.xml
    - backend/migrations/Version20260306210000.php
    - backend/src/Workflow/Application/Command/SetProcessDefinitionAccess/SetProcessDefinitionAccessCommand.php
    - backend/src/Workflow/Application/Command/SetProcessDefinitionAccess/SetProcessDefinitionAccessHandler.php
    - backend/src/Workflow/Application/Query/GetProcessDefinitionAccess/GetProcessDefinitionAccessQuery.php
    - backend/src/Workflow/Application/Query/GetProcessDefinitionAccess/GetProcessDefinitionAccessHandler.php
    - backend/src/Workflow/Application/DTO/ProcessDefinitionAccessDTO.php
    - backend/src/Workflow/Presentation/Security/ProcessDefinitionAccessChecker.php
  modified:
    - backend/src/Workflow/Presentation/Controller/ProcessDefinitionController.php
    - backend/src/Workflow/Presentation/Controller/ProcessInstanceController.php

key-decisions:
  - "ProcessDefinitionAccessChecker as dedicated service in Presentation layer — avoids injecting Organization repositories directly into controllers"
  - "Whitelist ACL: no rows = open to all, rows exist = restricted to matching dept/role combos"
  - "Owner bypass returns null from getAccessibleDefinitionIds — caller knows to skip filtering"
  - "DBAL UNION query for findAccessibleDefinitionIds — unrestricted defs + matching restricted defs in single query"
  - "ArrayParameterType::STRING (not Connection::PARAM_STR_ARRAY) — Doctrine DBAL API change"

patterns-established:
  - "ProcessDefinitionAccessChecker: centralized per-definition ACL resolution service"
  - "Whitelist ACL model: empty ACL rows = open access, present rows = restricted to matching entries"
  - "Owner bypass pattern: null return from access checker means skip all filtering"

requirements-completed: [PERM-03]

duration: 5min
completed: 2026-03-06
---

# Phase 13 Plan 02: Process Definition Access Summary

**Per-definition whitelist ACL with AccessType enum (view/start), DBAL UNION query for accessible IDs, and enforcement on list/start endpoints with org owner bypass**

## Performance

- **Duration:** 5 min
- **Started:** 2026-03-06T01:59:07Z
- **Completed:** 2026-03-06T02:04:50Z
- **Tasks:** 2
- **Files modified:** 15

## Accomplishments
- ProcessDefinitionAccess entity with whitelist ACL model (no rows = open to all, backward compatible)
- CQRS command/query for managing per-definition access rules with department/role name enrichment
- REST API endpoints GET/PUT on /process-definitions/{id}/access
- Enforcement on list endpoint (filters out inaccessible definitions) and start endpoint (blocks unauthorized starts)
- Organization owner bypasses all per-definition access checks

## Task Commits

Each task was committed atomically:

1. **Task 1: Create ProcessDefinitionAccess entity, repository, mapping, migration, and CQRS** - `1237e04` (feat)
2. **Task 2: Enforce per-definition access on list and start endpoints** - `5aae758` (feat)

## Files Created/Modified
- `backend/src/Workflow/Domain/Entity/ProcessDefinitionAccess.php` - Per-definition ACL entity with whitelist model
- `backend/src/Workflow/Domain/ValueObject/AccessType.php` - Enum: view, start
- `backend/src/Workflow/Domain/ValueObject/ProcessDefinitionAccessId.php` - UUID value object
- `backend/src/Workflow/Domain/Repository/ProcessDefinitionAccessRepositoryInterface.php` - Repository interface with findAccessibleDefinitionIds
- `backend/src/Workflow/Infrastructure/Repository/DoctrineProcessDefinitionAccessRepository.php` - DBAL UNION query for accessible IDs
- `backend/src/Workflow/Infrastructure/Persistence/Doctrine/Mapping/ProcessDefinitionAccess.orm.xml` - Doctrine XML mapping
- `backend/migrations/Version20260306210000.php` - Create workflow_process_definition_access table
- `backend/src/Workflow/Application/Command/SetProcessDefinitionAccess/` - CQRS command for setting ACL
- `backend/src/Workflow/Application/Query/GetProcessDefinitionAccess/` - CQRS query with dept/role name enrichment
- `backend/src/Workflow/Application/DTO/ProcessDefinitionAccessDTO.php` - DTO with department/role names
- `backend/src/Workflow/Presentation/Security/ProcessDefinitionAccessChecker.php` - Centralized ACL resolution service
- `backend/src/Workflow/Presentation/Controller/ProcessDefinitionController.php` - Added access endpoints + list filtering
- `backend/src/Workflow/Presentation/Controller/ProcessInstanceController.php` - Added start permission check

## Decisions Made
- Created ProcessDefinitionAccessChecker as a dedicated Presentation-layer service rather than injecting Organization repositories directly into controllers — cleaner separation, reusable
- Used DBAL UNION query (unrestricted defs + matching restricted defs) for efficient accessible definition resolution
- Owner bypass returns null from getAccessibleDefinitionIds, allowing caller to distinguish "show all" from "show these specific IDs"
- Used ArrayParameterType::STRING instead of deprecated Connection::PARAM_STR_ARRAY for DBAL array parameter binding
- Edge case: when user has no department and no roles, only unrestricted definitions are returned (early return optimization)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed DBAL constant reference**
- **Found during:** Task 1 (PHPStan verification)
- **Issue:** Connection::PARAM_STR_ARRAY is no longer a valid constant in Doctrine DBAL
- **Fix:** Replaced with ArrayParameterType::STRING enum
- **Files modified:** DoctrineProcessDefinitionAccessRepository.php, GetProcessDefinitionAccessHandler.php
- **Verification:** PHPStan passes at level 6

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** API change in DBAL, trivial fix. No scope creep.

## Issues Encountered
- Pre-existing schema drift between Doctrine mappings and database (not related to this plan's changes) — logged but not addressed

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Per-definition ACL infrastructure complete, ready for frontend integration (plan 03/04)
- ProcessDefinitionAccessChecker can be reused by future controllers needing per-definition access checks

---
*Phase: 13-granular-permissions-rbac*
*Completed: 2026-03-06*
