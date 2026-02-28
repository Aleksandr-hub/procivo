---
phase: 02-form-schema-and-assignment
verified: 2026-02-28T12:00:00Z
status: passed
score: 11/11 must-haves verified
re_verification: null
gaps: []
human_verification:
  - test: "Run the full workflow end-to-end: start a process instance that reaches a Task node, then GET /api/v1/organizations/{org}/tasks/{id} and confirm the response body contains a non-null form_schema with shared_fields and actions arrays"
    expected: "Response JSON includes form_schema.shared_fields and form_schema.actions matching the process definition's TaskNode config"
    why_human: "Requires a running Docker stack and a seeded process definition; cannot verify DB query results or HTTP response bodies programmatically in this verifier"
---

# Phase 02: Form Schema and Assignment Verification Report

**Phase Goal:** When a process reaches a Task node, OnTaskNodeActivated builds and snapshots the full form_schema into the created Task, resolves assignment strategy, and the API returns form_schema to callers
**Verified:** 2026-02-28T12:00:00Z
**Status:** passed
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | FormSchemaBuilder.build() returns schema with shared_fields and actions array | VERIFIED | `FormSchemaBuilder.php` lines 24-52; `FormSchemaBuilderTest.php` 8 tests pass |
| 2 | Each action in schema has its own form_fields plus action_key and label | VERIFIED | `build()` constructs `actions[]` with `key`, `label`, `form_fields` per outgoing transition |
| 3 | Task entity stores formSchema as nullable JSONB field | VERIFIED | `Task.php` line 35 (`private ?array $formSchema`), getter at line 223, `Task.orm.xml` line 22 (`type="json"`) |
| 4 | CreateTaskCommand and CreateTaskHandler pass formSchema through to Task::create | VERIFIED | `CreateTaskCommand.php` line 26 (`public ?array $formSchema`); `CreateTaskHandler.php` line 62 (`formSchema: $command->formSchema`) |
| 5 | TaskDTO includes formSchema from Task entity | VERIFIED | `TaskDTO.php` line 32 (`public ?array $formSchema`) + line 59 (`formSchema: $task->formSchema()`) |
| 6 | OnTaskNodeActivated builds form_schema via FormSchemaBuilder and passes it to CreateTaskCommand | VERIFIED | `OnTaskNodeActivated.php` lines 96-103 (builds schema), line 139 (`formSchema: $formSchema`); 4 tests pass |
| 7 | OnTaskNodeActivated loads ProcessDefinitionVersion to get ProcessGraph | VERIFIED | Constructor injects `ProcessDefinitionVersionRepositoryInterface`; lines 98-102 call `findById` then `ProcessGraph::fromSnapshot` |
| 8 | AssignmentResolver handles all 4 strategies: unassigned, specific_user, by_role, by_department | VERIFIED | `AssignmentResolver.php` `match($assignmentStrategy)` covers all 4 cases; 8 tests pass |
| 9 | Pool tasks with single candidate are auto-assigned (candidateRoleId/DepartmentId set to null) | VERIFIED | `resolveByRole`/`resolveByDepartment` return `new AssignmentResult(..., $candidates[0]['employeeId'], null, null)` when `count === 1` |
| 10 | Pool tasks with multiple candidates get candidateRoleId or candidateDepartmentId, assigneeId = null | VERIFIED | Both resolve methods return `null` assigneeId and populated candidate*Id when count > 1 |
| 11 | OrganizationQueryPort is used as ACL boundary for role/department member lookups | VERIFIED | `OrganizationQueryPort.php` interface exists; `AssignmentResolver` uses it exclusively for `findActiveEmployeeIdsByRoleId` and `findActiveEmployeeIdsByDepartmentId` |

**Score:** 11/11 truths verified

---

## Required Artifacts

| Artifact | Status | Lines | Details |
|----------|--------|-------|---------|
| `backend/src/Workflow/Application/Service/FormSchemaBuilder.php` | VERIFIED | 53 | Substantive — full `build()` implementation with ProcessGraph + FormFieldCollector |
| `backend/tests/Unit/Workflow/Application/Service/FormSchemaBuilderTest.php` | VERIFIED | 231 | 8 tests covering defaults, transitions, assignee injection, edge cases — all pass |
| `backend/migrations/Version20260228100000.php` | VERIFIED | 27 | `ALTER TABLE task_manager_tasks ADD COLUMN form_schema JSONB DEFAULT NULL` |
| `backend/src/TaskManager/Domain/Entity/Task.php` | VERIFIED | 243 | `formSchema` private field, parameter in `create()`, getter `formSchema()` |
| `backend/src/TaskManager/Infrastructure/Persistence/Doctrine/Mapping/Task.orm.xml` | VERIFIED | 39 | `<field name="formSchema" type="json" nullable="true" column="form_schema"/>` at line 22 |
| `backend/src/TaskManager/Application/Command/CreateTask/CreateTaskCommand.php` | VERIFIED | 29 | `public ?array $formSchema = null` added |
| `backend/src/TaskManager/Application/Command/CreateTask/CreateTaskHandler.php` | VERIFIED | 67 | `formSchema: $command->formSchema` passed to `Task::create()` |
| `backend/src/TaskManager/Application/DTO/TaskDTO.php` | VERIFIED | 63 | `formSchema` in constructor + `formSchema: $task->formSchema()` in `fromEntity()` |
| `backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php` | VERIFIED | 158 | Constructor injects `ProcessDefinitionVersionRepositoryInterface` and `FormSchemaBuilder`; builds schema lines 96-103; passes `formSchema:` to `CreateTaskCommand` line 139 |
| `backend/tests/Unit/Workflow/Application/EventHandler/OnTaskNodeActivatedTest.php` | VERIFIED | 356 | 4 tests: schema from builder, null version fallback, update path exclusion, assignment pass-through — all pass |
| `backend/tests/Unit/TaskManager/Application/Service/AssignmentResolverTest.php` | VERIFIED | 129 | 8 tests covering all 4 strategies + auto-assign for single-candidate pools — all pass |
| `backend/tests/Unit/TaskManager/Application/Command/CreateTaskHandlerTest.php` | VERIFIED | 153 | 3 tests: unassigned, AssignmentResolver delegation, formSchema pass-through — all pass |
| `backend/src/TaskManager/Application/Service/AssignmentResolver.php` | VERIFIED | 94 | All 4 strategy branches implemented; auto-assign logic in both `resolveByRole` and `resolveByDepartment` |
| `backend/src/TaskManager/Application/Port/OrganizationQueryPort.php` | VERIFIED | 29 | Interface defining ACL boundary; `findActiveEmployeeIdsByRoleId` and `findActiveEmployeeIdsByDepartmentId` methods present |

---

## Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `FormSchemaBuilder.php` | `FormFieldCollector.php` | Constructor injection, `injectAssigneeFieldsForDownstream` call | WIRED | Line 11-13 constructor, line 35 call |
| `CreateTaskHandler.php` | `Task.php` | `Task::create(formSchema: $command->formSchema)` | WIRED | Line 62: `formSchema: $command->formSchema` |
| `TaskDTO.php` | `Task.php` | `fromEntity` reads `formSchema()` | WIRED | Line 59: `formSchema: $task->formSchema()` |
| `OnTaskNodeActivated.php` | `FormSchemaBuilder.php` | Constructor injection, `formSchemaBuilder->build()` call | WIRED | Line 30 injection, line 101 `$this->formSchemaBuilder->build(...)` |
| `OnTaskNodeActivated.php` | `ProcessDefinitionVersionRepositoryInterface.php` | Constructor injection, `versionRepository->findById()` | WIRED | Line 29 injection, line 98 call |
| `CreateTaskHandler.php` | `AssignmentResolver.php` | Constructor injection, `assignmentResolver->resolve()` | WIRED | Line 20 injection, line 32 `$this->assignmentResolver->resolve(...)` |
| `AssignmentResolver.php` | `OrganizationQueryPort.php` | Constructor injection, `findActiveEmployeeIdsByRoleId` / `findActiveEmployeeIdsByDepartmentId` | WIRED | Line 13 injection, lines 51 and 76 calls |
| `TaskController::show()` | `TaskDTO.formSchema` | `json_encode($dto)` serializes all public properties | WIRED | Controller line 109: `json_decode(json_encode($dto, ...), true)` — `formSchema` is `public` on `TaskDTO` |

---

## Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-------------|-------------|--------|----------|
| FORM-01 | 02-01-PLAN.md | OnTaskNodeActivated builds form_schema from TaskNode config and outgoing transitions | SATISFIED | `OnTaskNodeActivated.php` lines 96-103; `FormSchemaBuilder.build()` extracts shared_fields and per-transition actions |
| FORM-02 | 02-01-PLAN.md | form_schema snapshotted into Task JSONB at creation time | SATISFIED | `Task.php` field + `Task.orm.xml` json mapping + migration `Version20260228100000.php` |
| FORM-03 | 02-01-PLAN.md | Each action in form_schema has its own set of fields plus shared fields from task node | SATISFIED | `FormSchemaBuilder.build()` returns `{shared_fields, actions[{key, label, form_fields}]}`; tests verify structure |
| FORM-04 | 02-01-PLAN.md | GET /api/v1/tasks/{id} returns form_schema alongside task data | SATISFIED | `TaskDTO.formSchema` is public; `TaskController::show()` serializes full DTO via `json_encode($dto)`; `GetTaskHandler` calls `TaskDTO::fromEntity()` which reads `$task->formSchema()` |
| ASGN-01 | 02-02-PLAN.md | Assignment strategies: unassigned, specific_user, by_role, by_department | SATISFIED | `AssignmentStrategy` enum + `AssignmentResolver` `match` covering all 4 cases |
| ASGN-02 | 02-02-PLAN.md | OnTaskNodeActivated resolves assignment strategy from node config | SATISFIED | `OnTaskNodeActivated.php` lines 57-89 read `assignment_strategy` from `taskConfig`, pass to `CreateTaskCommand` |
| ASGN-03 | 02-02-PLAN.md | Pool tasks (by_role, by_department) created with candidateRoleId/DepartmentId, assigneeId=null | SATISFIED | `AssignmentResolver` returns `candidateRoleId`/`candidateDepartmentId` when >1 candidates; tests `testResolveByRoleWithMultipleCandidates` and `testResolveByDepartmentWithMultipleCandidates` |
| ASGN-04 | 02-02-PLAN.md | Auto-assign when single candidate in pool | SATISFIED | `resolveByRole` / `resolveByDepartment` return `$candidates[0]['employeeId']` with null candidate*Id when count=1; tests verify |
| ASGN-07 | 02-02-PLAN.md | OrganizationQueryPort ACL layer for role/department member lookups | SATISFIED | `OrganizationQueryPort` interface is the sole port; `AssignmentResolver` uses it exclusively; no direct Organization module coupling |

**No orphaned requirements detected.** REQUIREMENTS.md Traceability table maps FORM-01..04 and ASGN-01..04, ASGN-07 all to Phase 2 — all covered by plans 02-01 and 02-02.

---

## Anti-Patterns Found

Anti-pattern scan performed on all 14 key files. No blockers or warnings detected.

| File | Pattern | Severity | Finding |
|------|---------|----------|---------|
| All phase files | TODO/FIXME/placeholder | — | None found |
| All phase files | Empty implementations (return null/\{\}/\[\]) | — | None found — all implementations are substantive |
| `OnTaskNodeActivated.php` | `$formSchema = null` with guard | INFO | Correct defensive null initialization; non-null only when version resolves; intentional fallback |

---

## Test Suite Results

| Suite | Tests | Assertions | Result |
|-------|-------|------------|--------|
| FormSchemaBuilderTest | 8 | — | All pass |
| OnTaskNodeActivatedTest | 4 | — | All pass |
| AssignmentResolverTest | 8 | — | All pass |
| CreateTaskHandlerTest | 3 | — | All pass |
| Full backend suite | 156 | 412 | All pass (2 PHPUnit deprecations, 9 notices — not failures) |

**Commit verification:** All 4 phase commits confirmed in git history (`f6cd52b`, `0786f6c`, `1a9d413`, `44b00c6`).

---

## Human Verification Required

### 1. End-to-end form_schema in API response

**Test:** Start a process instance that reaches a Task node (e.g., use the "Simple Task Process" template). Then call `GET /api/v1/organizations/{orgId}/tasks/{taskId}`. Inspect the response.
**Expected:** Response JSON contains `form_schema` with a non-null object having `shared_fields` (array) and `actions` (array of objects each with `key`, `label`, `form_fields`).
**Why human:** Requires a running Docker environment with a seeded ProcessDefinitionVersion, a live PostgreSQL with the `form_schema` column migrated, and an actual HTTP call to verify serialization round-trip through Doctrine JSON type and REST response.

---

## Gaps Summary

No gaps. All must-haves verified at all three levels (exists, substantive, wired). All 9 requirements covered. Full test suite green with 156 tests and 412 assertions.

---

_Verified: 2026-02-28T12:00:00Z_
_Verifier: Claude (gsd-verifier)_
