---
phase: 06-process-polish
plan: 01
status: complete
started: 2026-03-01
completed: 2026-03-01
---

## Summary

Refactored ProcessDefinition publish flow and resolved three backend tech debts.

## What Was Built

**Task 1 — Re-publish flow + FormSchemaBuilder dedup + snapshot serving:**
- `ProcessDefinition.publish()` now allows re-publishing from Published state (guards only Archived)
- `GetTaskWorkflowContextHandler` delegates to `FormSchemaBuilder::build()` instead of duplicating form schema logic
- `TaskController::show()` merges `form_schema` from Task snapshot when available

**Task 2 — FromVariable enum + resolver:**
- `AssignmentStrategy` enum gains `FromVariable = 'from_variable'` case (5 strategies total)
- `AssignmentResolver::resolve()` handles `FromVariable` with Unassigned fallback
- Unit test confirms FromVariable strategy falls back to Unassigned result

## Commits

| Hash | Message |
|------|---------|
| 56f3191 | feat(06-01): allow re-publishing from Published state and dedup FormSchemaBuilder |
| 7314db5 | feat(06-01): add FromVariable case to AssignmentStrategy enum with defensive resolver fallback |

## Key Files

### Created
- `backend/tests/Unit/TaskManager/Application/Service/AssignmentResolverTest.php` (FromVariable test case)

### Modified
- `backend/src/Workflow/Domain/Entity/ProcessDefinition.php` — publish() from Published state
- `backend/src/Workflow/Application/Query/GetTaskWorkflowContext/GetTaskWorkflowContextHandler.php` — FormSchemaBuilder delegation
- `backend/src/TaskManager/Presentation/Controller/TaskController.php` — snapshot-first form_schema
- `backend/src/TaskManager/Domain/ValueObject/AssignmentStrategy.php` — FromVariable case
- `backend/src/TaskManager/Application/Service/AssignmentResolver.php` — FromVariable branch

## Deviations

None.

## Self-Check: PASSED

- [x] publish() works from Published state
- [x] FormSchemaBuilder delegation in GetTaskWorkflowContextHandler
- [x] Snapshot-first form_schema in TaskController
- [x] FromVariable enum case exists
- [x] AssignmentResolver handles FromVariable
- [x] Unit test passes
