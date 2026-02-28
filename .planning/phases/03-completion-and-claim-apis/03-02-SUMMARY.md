---
phase: 03-completion-and-claim-apis
plan: 02
subsystem: api
tags: [pessimistic-locking, doctrine, claim, unclaim, transaction, phpunit]

# Dependency graph
requires:
  - phase: 03-completion-and-claim-apis
    provides: "Claim/unclaim commands and Task entity with pool task support"
provides:
  - "findByIdForUpdate() with PESSIMISTIC_WRITE lock in TaskRepositoryInterface"
  - "Transaction-wrapped ClaimTaskHandler preventing double-claim race condition"
  - "Transaction-wrapped UnclaimTaskHandler with ownership validation"
  - "9 unit tests covering all claim/unclaim scenarios"
affects: [03-completion-and-claim-apis]

# Tech tracking
tech-stack:
  added: []
  patterns: [pessimistic-locking-pattern, wrapInTransaction-for-CQRS-commands]

key-files:
  created:
    - backend/tests/Unit/TaskManager/Application/Command/ClaimTask/ClaimTaskHandlerTest.php
    - backend/tests/Unit/TaskManager/Application/Command/UnclaimTask/UnclaimTaskHandlerTest.php
  modified:
    - backend/src/TaskManager/Domain/Repository/TaskRepositoryInterface.php
    - backend/src/TaskManager/Infrastructure/Repository/DoctrineTaskRepository.php
    - backend/src/TaskManager/Application/Command/ClaimTask/ClaimTaskHandler.php
    - backend/src/TaskManager/Application/Command/UnclaimTask/UnclaimTaskHandler.php

key-decisions:
  - "wrapInTransaction auto-flushes -- no explicit save() inside transaction blocks"
  - "Real Task entities via Task::create() in tests instead of stubs -- verifies actual domain behavior"

patterns-established:
  - "Pessimistic locking pattern: findByIdForUpdate + wrapInTransaction for concurrent-safe mutations"
  - "wrapInTransaction callback pattern in unit tests: willReturnCallback(fn(callable $fn) => $fn())"

requirements-completed: [ASGN-05, ASGN-06]

# Metrics
duration: 3min
completed: 2026-02-28
---

# Phase 03 Plan 02: Pessimistic Locking Summary

**Pessimistic write lock (SELECT FOR UPDATE) in claim/unclaim handlers preventing double-claim race condition with 9 unit tests**

## Performance

- **Duration:** 3 min
- **Started:** 2026-02-28T11:40:20Z
- **Completed:** 2026-02-28T11:42:50Z
- **Tasks:** 2
- **Files modified:** 6

## Accomplishments
- Added `findByIdForUpdate()` with `PESSIMISTIC_WRITE` lock to repository interface and Doctrine implementation
- Wrapped ClaimTaskHandler and UnclaimTaskHandler in `entityManager->wrapInTransaction()` with pessimistic locking
- Removed explicit `save()` calls inside transactions (wrapInTransaction auto-flushes)
- Created 9 unit tests (5 claim + 4 unclaim) covering all scenarios: success, already claimed, non-pool task, not found, ineligible, wrong employee

## Task Commits

Each task was committed atomically:

1. **Task 1: Add findByIdForUpdate and wire pessimistic locking** - `45b6cf4` (feat)
2. **Task 2: Unit tests for ClaimTaskHandler and UnclaimTaskHandler** - `d7273aa` (test)

## Files Created/Modified
- `backend/src/TaskManager/Domain/Repository/TaskRepositoryInterface.php` - Added findByIdForUpdate(TaskId) contract
- `backend/src/TaskManager/Infrastructure/Repository/DoctrineTaskRepository.php` - Implemented findByIdForUpdate with LockMode::PESSIMISTIC_WRITE
- `backend/src/TaskManager/Application/Command/ClaimTask/ClaimTaskHandler.php` - Transaction-wrapped claim with pessimistic lock
- `backend/src/TaskManager/Application/Command/UnclaimTask/UnclaimTaskHandler.php` - Transaction-wrapped unclaim with pessimistic lock
- `backend/tests/Unit/TaskManager/Application/Command/ClaimTask/ClaimTaskHandlerTest.php` - 5 unit tests for claim handler
- `backend/tests/Unit/TaskManager/Application/Command/UnclaimTask/UnclaimTaskHandlerTest.php` - 4 unit tests for unclaim handler

## Decisions Made
- Used wrapInTransaction without explicit save() -- Doctrine's wrapInTransaction calls flush() before commit automatically, avoiding double-flush
- Created real Task entities via Task::create() in tests instead of mocking -- validates actual domain behavior (claim/unclaim state changes)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Pessimistic locking foundation complete for claim/unclaim APIs
- Ready for REST endpoint integration (controllers, routing)
- All handlers and tests pass PHPStan level 6

## Self-Check: PASSED

- All 6 source/test files verified present on disk
- Both task commits verified in git log: `45b6cf4`, `d7273aa`
- SUMMARY.md created at expected path

---
*Phase: 03-completion-and-claim-apis*
*Completed: 2026-02-28*
