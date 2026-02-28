---
phase: 02-form-schema-and-assignment
plan: 01
subsystem: workflow
tags: [form-schema, jsonb, process-graph, task-entity, doctrine]

# Dependency graph
requires:
  - phase: 01-backend-foundation
    provides: FormFieldCollector, ProcessGraph, FormSchemaValidator, ExpressionEvaluator
provides:
  - FormSchemaBuilder service for constructing form schemas from ProcessGraph + nodeId
  - Task entity formSchema JSONB field for snapshot-at-creation
  - CreateTaskCommand/Handler formSchema pass-through
  - TaskDTO formSchema exposure
affects: [02-form-schema-and-assignment, task-workflow-integration, frontend-forms]

# Tech tracking
tech-stack:
  added: []
  patterns: [form-schema-snapshot-at-creation, service-extraction-from-handler]

key-files:
  created:
    - backend/src/Workflow/Application/Service/FormSchemaBuilder.php
    - backend/tests/Unit/Workflow/Application/Service/FormSchemaBuilderTest.php
    - backend/migrations/Version20260228100000.php
  modified:
    - backend/src/TaskManager/Domain/Entity/Task.php
    - backend/src/TaskManager/Infrastructure/Persistence/Doctrine/Mapping/Task.orm.xml
    - backend/src/TaskManager/Application/Command/CreateTask/CreateTaskCommand.php
    - backend/src/TaskManager/Application/Command/CreateTask/CreateTaskHandler.php
    - backend/src/TaskManager/Application/DTO/TaskDTO.php

key-decisions:
  - "Form schema built by dedicated FormSchemaBuilder service extracted from handler logic"
  - "formSchema stored as nullable JSONB -- null for manual (non-workflow) tasks"

patterns-established:
  - "Service extraction pattern: complex logic extracted from handlers into reusable Application services"
  - "Snapshot-at-creation: form schema frozen into task to prevent schema drift on definition updates"

requirements-completed: [FORM-01, FORM-02, FORM-03, FORM-04]

# Metrics
duration: 3min
completed: 2026-02-28
---

# Phase 02 Plan 01: Form Schema Builder and Task Entity Integration Summary

**FormSchemaBuilder service extracting schema from ProcessGraph with JSONB snapshot storage in Task entity**

## Performance

- **Duration:** 3 min
- **Started:** 2026-02-28T11:00:24Z
- **Completed:** 2026-02-28T11:03:15Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments
- Created FormSchemaBuilder service that constructs form schema (shared_fields + per-action form_fields) from ProcessGraph and nodeId
- Added nullable formSchema JSONB field to Task entity with Doctrine mapping and migration
- Threaded formSchema through CreateTaskCommand/Handler into Task::create
- Exposed formSchema in TaskDTO via fromEntity
- 8 unit tests for FormSchemaBuilder covering defaults, assignee injection, and edge cases
- Full test suite (141 tests) green with zero regressions

## Task Commits

Each task was committed atomically:

1. **Task 1: Create FormSchemaBuilder service and unit tests** - `f6cd52b` (feat)
2. **Task 2: Add formSchema JSONB field to Task entity, Doctrine mapping, migration, CreateTaskCommand, CreateTaskHandler, and TaskDTO** - `0786f6c` (feat)

## Files Created/Modified
- `backend/src/Workflow/Application/Service/FormSchemaBuilder.php` - Builds form schema from ProcessGraph for a task node
- `backend/tests/Unit/Workflow/Application/Service/FormSchemaBuilderTest.php` - 8 unit tests for FormSchemaBuilder
- `backend/migrations/Version20260228100000.php` - Adds form_schema JSONB column to task_manager_tasks
- `backend/src/TaskManager/Domain/Entity/Task.php` - Added formSchema field, create param, and getter
- `backend/src/TaskManager/Infrastructure/Persistence/Doctrine/Mapping/Task.orm.xml` - Added json field mapping for formSchema
- `backend/src/TaskManager/Application/Command/CreateTask/CreateTaskCommand.php` - Added formSchema param
- `backend/src/TaskManager/Application/Command/CreateTask/CreateTaskHandler.php` - Passes formSchema to Task::create
- `backend/src/TaskManager/Application/DTO/TaskDTO.php` - Exposes formSchema from entity

## Decisions Made
- FormSchemaBuilder extracted from GetTaskWorkflowContextHandler as a dedicated Application service for reusability
- formSchema is nullable JSONB -- manual (non-workflow) tasks have null, no backfill needed for existing data

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- FormSchemaBuilder ready for Plan 02 to call from OnTaskNodeActivated when creating workflow tasks
- Task entity accepts formSchema at creation time
- TaskDTO exposes formSchema for frontend consumption

## Self-Check: PASSED

- All 3 created files exist on disk
- Commit f6cd52b (Task 1) verified in git log
- Commit 0786f6c (Task 2) verified in git log
- 141 tests pass (8 new + 133 existing)

---
*Phase: 02-form-schema-and-assignment*
*Completed: 2026-02-28*
