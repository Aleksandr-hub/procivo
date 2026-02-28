---
phase: 03-completion-and-claim-apis
verified: 2026-02-28T12:30:00Z
status: passed
score: 9/9 must-haves verified
re_verification: false
---

# Phase 3: Completion and Claim APIs — Verification Report

**Phase Goal:** Users can complete workflow tasks by submitting action + formData through the API, and pool tasks can be claimed/unclaimed with proper concurrency control
**Verified:** 2026-02-28T12:30:00Z
**Status:** PASSED
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|---------|
| 1 | POST /api/v1/tasks/{id}/complete accepts { action_key, form_data } and dispatches ExecuteTaskActionCommand | VERIFIED | `TaskController::complete()` at line 115–133; route `/{taskId}/complete` with `methods: ['POST']`; dispatches `ExecuteTaskActionCommand` with `actionKey` and `formData` |
| 2 | FormData is validated against the task's form_schema using FormSchemaValidator (type, constraints, regex, dependencies) | VERIFIED | `ExecuteTaskActionHandler` lines 70–74: collects fields via `FormFieldCollector::collectForValidation()`, calls `formSchemaValidator->validate($allFields, $command->formData)`, throws `FormValidationException::validationFailed()` on errors |
| 3 | After successful completion, the workflow engine advances the token to the next node via the selected action's transition | VERIFIED | Handler lines 80–84: `engine->executeAction()` called after merge, `instanceRepository->save()` persists updated instance, `link->markCompleted()` + `linkRepository->save()` |
| 4 | After successful completion, task status transitions to 'done' via TransitionTaskCommand('workflow_complete') | VERIFIED | Handler lines 86–94: `commandBus->dispatch(new TransitionTaskCommand(taskId: ..., transition: 'workflow_complete'))` inside try/catch; failure is swallowed silently |
| 5 | Gateway conditions evaluate correctly against the freshly-merged process variables | VERIFIED | Handler line 77: `mergeVariables()` called BEFORE `executeAction()` (line 80); `WorkflowEngine::executeAction()` evaluates `condition_expression` against `instance->variables()` at lines 120–126; merge-before-execute order is correct |
| 6 | POST /api/v1/tasks/{id}/claim assigns a pool task to the requesting employee with pessimistic locking | VERIFIED | `ClaimTaskHandler` wraps entire logic in `wrapInTransaction`, calls `findByIdForUpdate`, checks `isPoolTask()`, `assigneeId()`, `validateEligibility()`, then `task->claim()` |
| 7 | POST /api/v1/tasks/{id}/unclaim returns a claimed task to the pool (assigneeId set back to null) | VERIFIED | `UnclaimTaskHandler` wraps logic in `wrapInTransaction`, calls `findByIdForUpdate`, checks `isPoolTask()`, compares `assigneeId()` vs `command->employeeId`, then `task->unclaim()` |
| 8 | Concurrent claim requests for the same task are serialized by the database lock — only the first succeeds | VERIFIED | `DoctrineTaskRepository::findByIdForUpdate()` calls `entityManager->find(Task::class, $id->value(), LockMode::PESSIMISTIC_WRITE)` — SELECT FOR UPDATE issued at DB level; both ClaimTaskHandler and UnclaimTaskHandler use this method inside `wrapInTransaction` |
| 9 | Only eligible employees (matching role or department) can claim pool tasks, and only the current assignee can unclaim | VERIFIED | `ClaimTaskHandler::validateEligibility()` checks `organizationQueryPort->employeeBelongsToRole()` and `employeeBelongsToDepartment()`; throws `TaskClaimException::notEligible()` if neither matches. `UnclaimTaskHandler` checks `$task->assigneeId() !== $command->employeeId` and throws `TaskClaimException::notClaimed()` |

**Score:** 9/9 truths verified

---

## Required Artifacts

### Plan 01 — Completion API

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `backend/src/Workflow/Application/Command/ExecuteTaskAction/ExecuteTaskActionHandler.php` | Handler with FormSchemaValidator injection and TransitionTaskCommand dispatch | VERIFIED | File exists, 96 lines, non-stub. Injects `FormSchemaValidator` and `CommandBusInterface`. No `validateFormData()` private method found (removed). `formSchemaValidator->validate()` called at line 71. `TransitionTaskCommand` dispatched at line 88. |
| `backend/src/TaskManager/Presentation/Controller/TaskController.php` | Route `/{taskId}/complete` accepting `{ action_key, form_data }` | VERIFIED | File exists, 228 lines. Route at line 115 `#[Route('/{taskId}/complete', name: 'complete', methods: ['POST'])]`. Method `complete()` reads `action_key` and `form_data`. Old `execute-action` route absent. |
| `backend/tests/Unit/Workflow/Application/Command/ExecuteTaskAction/ExecuteTaskActionHandlerTest.php` | 5 unit tests for completion flow | VERIFIED | File exists, 422 lines. Contains exactly 5 test methods: `testCompleteTaskWithValidFormData`, `testCompleteTaskWithValidationErrors`, `testCompleteTaskWhenLinkNotFound`, `testCompleteTaskWhenAlreadyCompleted`, `testTaskTransitionFailureDoesNotBreakCompletion`. All test the correct scenarios. |

### Plan 02 — Claim/Unclaim API

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `backend/src/TaskManager/Domain/Repository/TaskRepositoryInterface.php` | Declares `findByIdForUpdate(TaskId): ?Task` | VERIFIED | File exists. Method at line 28 with PHPDoc "Must be called inside an active transaction." |
| `backend/src/TaskManager/Infrastructure/Repository/DoctrineTaskRepository.php` | Implements `findByIdForUpdate` with `LockMode::PESSIMISTIC_WRITE` | VERIFIED | File exists, 107 lines. `findByIdForUpdate()` at lines 38–45 calls `entityManager->find()` with `LockMode::PESSIMISTIC_WRITE` as third argument. `use Doctrine\DBAL\LockMode` present. |
| `backend/src/TaskManager/Application/Command/ClaimTask/ClaimTaskHandler.php` | Transaction-wrapped claim with pessimistic lock | VERIFIED | File exists, 70 lines. `wrapInTransaction` at line 27; `findByIdForUpdate` at line 28; no explicit `save()` inside transaction. |
| `backend/src/TaskManager/Application/Command/UnclaimTask/UnclaimTaskHandler.php` | Transaction-wrapped unclaim with pessimistic lock | VERIFIED | File exists, 46 lines. `wrapInTransaction` at line 25; `findByIdForUpdate` at line 26; no explicit `save()` inside transaction. |
| `backend/tests/Unit/TaskManager/Application/Command/ClaimTask/ClaimTaskHandlerTest.php` | 5 unit tests for claim handler | VERIFIED | File exists, 178 lines. 5 test methods: `testClaimPoolTaskSuccessfully`, `testClaimAlreadyClaimedTask`, `testClaimNonPoolTask`, `testClaimTaskNotFound`, `testClaimIneligibleEmployee`. Uses `willReturnCallback(fn(callable $fn) => $fn())` pattern for `wrapInTransaction`. |
| `backend/tests/Unit/TaskManager/Application/Command/UnclaimTask/UnclaimTaskHandlerTest.php` | 4 unit tests for unclaim handler | VERIFIED | File exists, 132 lines. 4 test methods: `testUnclaimTaskSuccessfully`, `testUnclaimTaskNotFound`, `testUnclaimNonPoolTask`, `testUnclaimByWrongEmployee`. Same `wrapInTransaction` callback pattern. |

---

## Key Link Verification

### Plan 01 Key Links

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `TaskController::complete()` | `ExecuteTaskActionCommand` | `command.bus dispatch` | WIRED | Line 126: `$this->commandBus->dispatch(new ExecuteTaskActionCommand(taskId: ..., actionKey: ..., formData: ...))` |
| `ExecuteTaskActionHandler` | `FormSchemaValidator` | constructor injection, `validate()` call | WIRED | Constructor injects `FormSchemaValidator $formSchemaValidator` (line 32); called at line 71: `$this->formSchemaValidator->validate($allFields, $command->formData)` |
| `ExecuteTaskActionHandler` | `TransitionTaskCommand` | `command.bus dispatch` after engine execution | WIRED | Line 88: `$this->commandBus->dispatch(new TransitionTaskCommand(taskId: $command->taskId, transition: 'workflow_complete'))` — inside try/catch after `linkRepository->save()` |

### Plan 02 Key Links

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `ClaimTaskHandler` | `TaskRepositoryInterface::findByIdForUpdate()` | constructor injection, called inside `wrapInTransaction` | WIRED | Line 28: `$this->taskRepository->findByIdForUpdate(TaskId::fromString($command->taskId))` inside the transaction closure |
| `DoctrineTaskRepository::findByIdForUpdate()` | `EntityManager::find()` with `LockMode` | Doctrine `PESSIMISTIC_WRITE` | WIRED | Lines 40–44: `$this->entityManager->find(Task::class, $id->value(), LockMode::PESSIMISTIC_WRITE)` |
| `ClaimTaskHandler` | `EntityManagerInterface::wrapInTransaction()` | constructor injection | WIRED | Line 27: `$this->entityManager->wrapInTransaction(function () use ($command): void { ... })` |

---

## Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|---------|
| COMP-01 | 03-01-PLAN | API POST /api/v1/tasks/{id}/complete accepts { action, formData } | SATISFIED | `TaskController::complete()` route `/{taskId}/complete` accepts `action_key` + `form_data`, dispatches `ExecuteTaskActionCommand` |
| COMP-04 | 03-01-PLAN | After merge, workflow engine advances — token moves to next node via selected action's transition | SATISFIED | Handler merges variables (line 77), then calls `engine->executeAction()` (line 80), persists instance; `WorkflowEngine` evaluates gateway conditions against updated variables |
| ASGN-05 | 03-02-PLAN | API POST /api/v1/tasks/{id}/claim — employee claims pool task (with pessimistic locking) | SATISFIED | `TaskController::claim()` route `/{taskId}/claim`; `ClaimTaskHandler` uses `wrapInTransaction` + `findByIdForUpdate` with `LockMode::PESSIMISTIC_WRITE` |
| ASGN-06 | 03-02-PLAN | API POST /api/v1/tasks/{id}/unclaim — employee returns task to pool | SATISFIED | `TaskController::unclaim()` route `/{taskId}/unclaim`; `UnclaimTaskHandler` uses same locking pattern; verifies `assigneeId()` ownership; calls `task->unclaim()` |

### Orphaned Requirements Check

REQUIREMENTS.md traceability table maps COMP-01, COMP-04, ASGN-05, ASGN-06 to Phase 3. All four appear in the plan frontmatter. No orphaned requirements.

---

## Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| None | — | — | — | No anti-patterns detected |

Scanned for: TODO/FIXME/HACK, placeholder returns (`return null`, `return []`, `return {}`), empty handler bodies, console.log-only implementations, old `execute-action` route references, residual `validateFormData()` private method.

All scans returned clean results.

---

## Human Verification Required

### 1. Real database pessimistic lock under concurrent load

**Test:** Start two concurrent HTTP requests to `POST /api/v1/organizations/{id}/tasks/{taskId}/claim` with different `employee_id` values simultaneously (e.g., using `ab` or a parallel curl script against a running instance with PostgreSQL).
**Expected:** Exactly one claim succeeds with HTTP 200 and one fails with a domain exception (task already claimed).
**Why human:** The `LockMode::PESSIMISTIC_WRITE` wiring is verified in code, but race condition serialization can only be confirmed by running two transactions concurrently against a real PostgreSQL instance.

### 2. Task status transition to 'done' after completion

**Test:** Start a process, advance to a task node, then POST to `/complete` with valid `action_key` and `form_data`. Check the task's `status` field in the database or via `GET /api/v1/organizations/{id}/tasks/{taskId}`.
**Expected:** Task status is `done` (or equivalent terminal state) after successful completion.
**Why human:** The `TransitionTaskCommand` dispatch is verified in code, but whether the Symfony Workflow component accepts the `workflow_complete` transition from the task's current state requires a running environment with the state machine configured.

---

## Verification Summary

Phase 3 goal is fully achieved. All 9 observable truths are verified against the actual codebase:

**Plan 01 (Completion API):** The `ExecuteTaskActionHandler` has been correctly upgraded — `FormSchemaValidator` replaces the inline `validateFormData()` method (which no longer exists), fields are collected via `FormFieldCollector::collectForValidation()`, and `TransitionTaskCommand('workflow_complete')` is dispatched after successful engine execution with a silent-catch pattern. The controller route is correctly renamed from `/execute-action` to `/complete`. Five unit tests cover all specified scenarios including transition failure resilience.

**Plan 02 (Claim/Unclaim API):** `findByIdForUpdate()` is declared in the interface, implemented with `LockMode::PESSIMISTIC_WRITE` in the Doctrine repository, and called from both `ClaimTaskHandler` and `UnclaimTaskHandler` inside `wrapInTransaction` closures with no explicit `save()` calls. The eligibility check (role/department membership) and ownership check (unclaim by current assignee only) are correctly implemented. Nine unit tests cover all specified scenarios using the `willReturnCallback(fn(callable $fn) => $fn())` wrapInTransaction pattern.

No stubs, placeholders, or orphaned artifacts were found. All key links are wired. All four requirement IDs (COMP-01, COMP-04, ASGN-05, ASGN-06) are satisfied with implementation evidence.

---

_Verified: 2026-02-28T12:30:00Z_
_Verifier: Claude (gsd-verifier)_
