---
phase: 02-form-schema-and-assignment
plan: 02
subsystem: workflow
tags: [form-schema, assignment, unit-tests, event-handler, acl-boundary]

# Dependency graph
requires:
  - phase: 02-form-schema-and-assignment
    provides: FormSchemaBuilder service, Task entity formSchema field, CreateTaskCommand formSchema param
provides:
  - OnTaskNodeActivated wired to build and pass formSchema via FormSchemaBuilder
  - Complete unit test coverage for assignment pipeline (AssignmentResolver, CreateTaskHandler)
  - OnTaskNodeActivated unit tests for form schema integration
affects: [frontend-forms, task-workflow-integration]

# Tech tracking
tech-stack:
  added: []
  patterns: [stub-over-mock-for-happy-path, real-collaborator-for-final-classes]

key-files:
  created:
    - backend/tests/Unit/Workflow/Application/EventHandler/OnTaskNodeActivatedTest.php
    - backend/tests/Unit/TaskManager/Application/Service/AssignmentResolverTest.php
    - backend/tests/Unit/TaskManager/Application/Command/CreateTaskHandlerTest.php
  modified:
    - backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php

key-decisions:
  - "Real FormSchemaBuilder + FormFieldCollector used in tests instead of mocking final readonly classes"
  - "Real AssignmentResolver with stubbed OrganizationQueryPort in CreateTaskHandler tests"

patterns-established:
  - "Final readonly service testing: use real instances with stubbed ports instead of attempting to mock"
  - "Command capture pattern: stub commandBus.dispatch with callback to collect dispatched commands"

requirements-completed: [ASGN-01, ASGN-02, ASGN-03, ASGN-04, ASGN-07]

# Metrics
duration: 5min
completed: 2026-02-28
---

# Phase 02 Plan 02: Form Schema Wiring and Assignment Pipeline Tests Summary

**OnTaskNodeActivated wired to FormSchemaBuilder with 15 unit tests covering form schema integration and all 4 assignment strategies**

## Performance

- **Duration:** 5 min
- **Started:** 2026-02-28T11:05:39Z
- **Completed:** 2026-02-28T11:11:04Z
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments
- Wired OnTaskNodeActivated to load ProcessDefinitionVersion and build form schema via FormSchemaBuilder, passing it to CreateTaskCommand on new task creation
- Created 4 unit tests for OnTaskNodeActivated covering: schema from builder, null version handling, update path exclusion, assignment config pass-through
- Created 8 unit tests for AssignmentResolver covering all 4 strategies (unassigned, specific_user, by_role, by_department) with auto-assign for single-candidate pools
- Created 3 unit tests for CreateTaskHandler covering unassigned strategy, delegation to AssignmentResolver, and formSchema pass-through
- Full test suite green: 156 tests (15 new + 141 existing)

## Task Commits

Each task was committed atomically:

1. **Task 1: Wire OnTaskNodeActivated to build and pass formSchema** - `1a9d413` (feat)
2. **Task 2: Create AssignmentResolver and CreateTaskHandler unit tests** - `44b00c6` (test)

## Files Created/Modified
- `backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php` - Added ProcessDefinitionVersionRepository and FormSchemaBuilder deps, builds formSchema from ProcessGraph
- `backend/tests/Unit/Workflow/Application/EventHandler/OnTaskNodeActivatedTest.php` - 4 tests for form schema wiring and assignment forwarding
- `backend/tests/Unit/TaskManager/Application/Service/AssignmentResolverTest.php` - 8 tests for all 4 assignment strategies with auto-assign
- `backend/tests/Unit/TaskManager/Application/Command/CreateTaskHandlerTest.php` - 3 tests for handler delegation and formSchema pass-through

## Decisions Made
- Used real FormSchemaBuilder + FormFieldCollector instances in OnTaskNodeActivated tests (final readonly classes cannot be mocked, and creating real instances was straightforward since they have no external dependencies)
- Used real AssignmentResolver with stubbed OrganizationQueryPort in CreateTaskHandler tests (same reasoning -- final readonly, simple construction)
- Used createStub over createMock for all dependencies where only return values matter (PHPUnit 13 best practice)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Null-safe guard on instance before accessing versionId()**
- **Found during:** Task 1 (OnTaskNodeActivated modification)
- **Issue:** formSchema building code accessed `$instance->versionId()` without null check; `$instance` can be null when `instanceRepository->findById()` returns null
- **Fix:** Wrapped version lookup and schema building in `if (null !== $instance)` guard
- **Files modified:** backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php
- **Verification:** testNewTaskGetsNullFormSchemaWhenVersionNotFound passes
- **Committed in:** 1a9d413 (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** Essential null safety fix. No scope creep.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Form schema snapshot flow is complete end-to-end: event -> schema builder -> command -> entity
- Assignment pipeline verified with unit tests for all 4 strategies
- OrganizationQueryPort ACL boundary confirmed as sole interface for organization lookups
- Ready for frontend form rendering (Phase 3+)

## Self-Check: PASSED

- All 3 created files exist on disk
- Commit 1a9d413 (Task 1) verified in git log
- Commit 44b00c6 (Task 2) verified in git log
- 156 tests pass (15 new + 141 existing)

---
*Phase: 02-form-schema-and-assignment*
*Completed: 2026-02-28*
