# Pitfalls Research

**Domain:** BPM Workflow-Task Integration (dynamic forms, expression evaluation, pool assignment)
**Researched:** 2026-02-28
**Confidence:** HIGH (grounded in existing Procivo codebase + deep BPM domain knowledge from Camunda, Activiti, Bonita, Appian, BPMN 2.0 spec)

---

## Critical Pitfalls

### Pitfall 1: Form Schema Snapshot vs. Live Reference

**What goes wrong:**
The form schema (shared fields + action definitions) is read from the ProcessDefinition at task creation time and embedded in the Task. When the process designer later updates the definition (adds a required field, renames an action), tasks already in flight use the stale snapshot. Users can submit a form missing the new required field, or the backend rejects data the user correctly filled according to the old schema.

**Why it happens:**
Developers store a reference to the process definition version rather than a full snapshot in the task. Alternatively, they store a snapshot but don't regenerate it when the definition is republished with a new version. It feels wasteful to duplicate the schema per task when the definition already has it.

**How to avoid:**
Always snapshot the full `form_schema` (shared fields + all actions with their fields) into the Task at creation time. Store it as JSONB in `Task.metadata` or a dedicated column. Never re-read from the process definition when serving the task to the user. Use the ProcessDefinitionVersion ID already on WorkflowTaskLink to clarify which version the task was created from — it is for audit only, not for live schema resolution.

**Warning signs:**
- `OnTaskNodeActivated` does not copy `formFields` and action configurations into the task metadata
- Task detail API reads form schema by joining back to `ProcessDefinition` instead of reading from the task itself
- Updating a process definition while tasks are in flight causes validation errors on existing tasks

**Phase to address:**
Session 2 (Task form schema) — when `OnTaskNodeActivated` is updated to build and embed `form_schema`.

---

### Pitfall 2: Variable Key Collisions Between Stages

**What goes wrong:**
Two different task nodes in the same process define a form field named `comment`. When the first stage submits `{ comment: "Looks good" }`, it is merged into `ProcessInstance.variables`. The second stage overwrites the same key with `{ comment: "Needs revision" }`. The first stage's data is silently lost. This also breaks gateway conditions that relied on the first value.

**Why it happens:**
Developers use natural field names without namespacing. The `mergeVariables` call does a flat `array_merge`, which overwrites. It is not obvious during design-time that two nodes will collide.

**How to avoid:**
Namespace all submitted form data with the node ID and/or action key when merging into process variables. Convention: `{nodeId}.{fieldKey}` or `{actionKey}.{fieldKey}`. Example: `review_docs.comment` vs `final_approval.comment`. Expose the namespaced key in the condition expression UI so designers can write `review_docs.comment == "approved"` instead of just `comment == "approved"`. The current `VariablesMergedEvent` already carries `nodeId` and `actionKey` — use them to prefix keys.

**Warning signs:**
- `CompleteTaskNode` handler uses `array_merge($instance->variables(), $formData)` without prefixing
- Condition expressions in the designer use bare field names like `status` or `comment`
- Two task nodes in a template use the same field names (inspect all 6 templates)

**Phase to address:**
Session 1 (Backend foundation) — the merge strategy must be established before any form data flows.

---

### Pitfall 3: ExpressionEvaluator Silently Swallows Errors

**What goes wrong:**
The current `ExpressionEvaluator.evaluate()` catches `SyntaxError` and returns `false`. A process designer writes a condition `approval_status == "approved"` but forgets to namespace correctly — the variable is actually `review.approval_status`. The expression evaluates to `false` (because the variable is not in scope, no exception thrown), and the XOR gateway always takes the default or rejection path. The process appears to work but routes all instances incorrectly. This is extremely hard to diagnose in production.

**Why it happens:**
Catch-all error handling is a common defensive pattern. But for expressions referencing undefined variables, Symfony ExpressionLanguage does not throw — it just resolves the undefined name as `null`. The condition `null == "approved"` evaluates to `false`. No error, wrong result.

**How to avoid:**
1. At process definition publish time, validate all condition expressions against the known variable namespace. Reject publication if an expression references a key that no upstream task node produces.
2. At gateway evaluation time, log a structured warning when an expression references a variable key not present in `ProcessInstance.variables`. Do not silently return `false`.
3. Add a test: process with a XOR gateway where the condition variable is deliberately absent — assert the warning is logged and the default transition is taken (not silently the first non-default).

**Warning signs:**
- `ExpressionEvaluator.evaluate()` only catches `SyntaxError` but not undefined variable access (current code)
- No validation of condition expressions against form field keys at publish time
- XOR gateway in test always routes to "happy path" but never the rejection path in integration tests

**Phase to address:**
Session 1 (Backend foundation, ExpressionEvaluator integration) + process definition publish validation.

---

### Pitfall 4: Race Condition on Pool Task Claim

**What goes wrong:**
Two users in the same role pool both see the same available task. Both click "Claim" within milliseconds of each other. Without a database-level lock, both reads return `assigneeId = null`, both pass validation, and both writes succeed — resulting in the task showing two different assignees depending on which connection committed last. One user proceeds to work while the other also proceeds in their session, unaware of the conflict.

**Why it happens:**
The claim logic checks `if (null !== $this->assigneeId)` at the domain level, which only works if the entity was loaded with a fresh read inside a serializable transaction. Most developers use optimistic locking or forget to add database constraints.

**How to avoid:**
Use a pessimistic lock (`SELECT FOR UPDATE`) when loading the Task entity for claim/unclaim operations. In Doctrine, use `LockMode::PESSIMISTIC_WRITE` on `$em->find(Task::class, $id, LockMode::PESSIMISTIC_WRITE)`. The domain's `Task::claim()` already throws `TaskClaimException::alreadyClaimed()` — the lock ensures the in-memory check reflects the database truth. Alternatively, add a unique partial index on `tasks` where `assignee_id IS NULL AND candidate_role_id IS NOT NULL` combined with an UPDATE with a WHERE clause.

**Warning signs:**
- `ClaimTaskHandler` loads the Task without any lock mode
- No integration test with concurrent claim attempts
- No HTTP 409 response documented in API spec for the race case (it is documented in the spec but not tested)

**Phase to address:**
Session covering ClaimTask/UnclaimTask command handlers. Must be addressed before the feature is tested with two browser sessions.

---

### Pitfall 5: Token-Task Link Breaks When Task is Reassigned Between Nodes

**What goes wrong:**
`OnTaskNodeActivated` currently checks `findLatestByProcessInstanceId` to decide whether to create a new task or update the existing one. For a linear process (Start → Task → End), this works. For a looping process (Task A → reject → Task A again), the handler finds the existing link and updates the task title/priority — but it reuses the same `taskId`. The token has a different ID on the second pass, but the link still points to the old token. When `CompleteTaskNode` looks up the task by the current tokenId, it finds no link and throws.

**Why it happens:**
The current link resolution uses `findLatestByProcessInstanceId` — a process-scoped lookup — rather than a token-scoped lookup. For single-task processes this is fine. For looping or parallel processes, one process instance creates multiple task nodes, requiring a link per token, not per process instance.

**How to avoid:**
Change `WorkflowTaskLink` resolution to be token-scoped: `findByProcessInstanceIdAndTokenId(processInstanceId, tokenId)`. Each token activation creates a new task and a new link. `CompleteTaskNode` looks up the link by `processInstanceId + tokenId`, not just `processInstanceId`. The `WorkflowTaskLinkRepositoryInterface` already likely has this gap — audit its query methods now.

**Warning signs:**
- `findLatestByProcessInstanceId` used in `OnTaskNodeActivated` (visible in current code)
- No test for a process that visits the same task node twice (e.g., rejection loop)
- Parallel gateway creates 2 task nodes simultaneously — both fire `TaskNodeActivated` with different tokenIds, but the second call finds the first link and reuses the task

**Phase to address:**
Session 2 (Task form schema) — resolve the link strategy before parallel/loop scenarios are tested.

---

### Pitfall 6: Form Validation on the Frontend Only

**What goes wrong:**
Required field validation (`required: true`, `min/max`, `regex`) is implemented only in the Vue form component. A user submits a raw POST to `/api/v1/tasks/{id}/complete` with `formData: {}`, skipping all required fields. The backend merges the empty object into process variables. The XOR gateway then evaluates conditions against missing variables, either silently routing to the default or throwing an execution exception mid-process — leaving the process instance in an unrecoverable state.

**Why it happens:**
Frontend validation is easy to see and test visually. Backend validation of dynamic schemas feels complex — the schema is JSONB, so it cannot be validated with a static Symfony Form type. Developers defer it or forget it.

**How to avoid:**
`CompleteTaskNodeHandler` must re-read the `form_schema` from the Task's metadata and validate the submitted `formData` against it. Build a `FormSchemaValidator` service that accepts `(array $formSchema, array $formData)` and returns violations. Required field check: key must exist and not be empty. Type check: value must match declared type (`string`, `number`, `date`, `boolean`). Min/max: numeric range check. Regex: `preg_match`. This service is pure domain logic with no Symfony Form dependency.

**Warning signs:**
- `CompleteTaskNodeHandler` does not reference the Task's `form_schema` at all
- No unit test for submitting empty `formData` on a task with required fields
- Validation only exists in Vue component (`DynamicFormField.vue`)

**Phase to address:**
Session 2 (Task form schema) + Session 3 (form submission endpoint).

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Store form schema as flat JSONB without versioning | Simple implementation | Cannot diff what schema was active at submission time for audit | Never — add `form_schema_version` alongside schema |
| Use bare variable keys (no node prefix) in process variables | Cleaner expressions (`comment` vs `stage1.comment`) | Key collisions across stages, silent data loss | Only if each process has a guaranteed unique field namespace (unlikely) |
| Validate form fields only in Vue | Faster frontend dev | Any API client bypasses validation; process corruption possible | Never for required fields and type safety |
| Resolve form schema from ProcessDefinition at serve time (not snapshot) | No data duplication | Schema drift for in-flight tasks after republish | Never — snapshot at creation time |
| `LockMode::NONE` on claim handler | No extra DB round-trip | Race condition allows double-claim in concurrent scenarios | Never in production; acceptable in single-user dev |
| `findLatestByProcessInstanceId` for link resolution | Simpler query | Breaks on loops and parallel branches | Only for provably linear single-task processes (not a good constraint) |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Symfony ExpressionLanguage + JSONB variables | Passing typed PHP values (`int`, `bool`) from JSONB as strings because JSON decodes to string | Ensure `ProcessInstance.variables` preserves native PHP types via Doctrine JSONB type mapping; `json_decode($value, true)` preserves `int` and `bool` |
| Symfony Messenger event.bus + CommandBus inside event handler | `OnTaskNodeActivated` dispatches `CreateTaskCommand` synchronously; if command handler throws, the event handler fails and the event is retried — causing duplicate task creation | Make task creation idempotent: check for existing link by processInstanceId+tokenId before creating; or use database unique constraint on (processInstanceId, tokenId) in WorkflowTaskLink |
| Doctrine XML mapping + JSONB columns | Adding new JSONB column (`form_schema`) to Task without a migration causes silent `null` on existing tasks — no error, no schema, broken task detail page | Always generate migration immediately after adding the column to the XML mapping; test with a task created before migration |
| RBAC scope on AssignmentResolver queries | `by_role` strategy queries all employees with a given role globally — ignores organization boundary | All assignment resolution queries must be scoped by `organizationId` from the process instance |
| WorkflowTaskLink + task deletion | Task is manually deleted by a user but WorkflowTaskLink still points to it; `CompleteTaskNode` looks up the link, tries to load the task, gets null, throws | Add cascade or orphan check: when a task linked to a process is deleted, either block deletion or cancel the corresponding process token |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Loading full ProcessInstance event stream for every CompleteTaskNode call | Slow task completion as process runs longer (more events = longer replay) | Add a snapshot mechanism: store current `variables` + `tokens` state in the DB after every N events; reconstitute from snapshot + delta | Around 100+ events per process instance |
| N+1 query on task list with process context | `ListTasksHandler` loads all tasks, then for each task makes a separate query to WorkflowTaskLink + ProcessInstance to get process name/stage | Join WorkflowTaskLink in the ListTasks query and carry process context in `TaskDTO` directly | > 20 tasks on screen |
| Querying candidate pool membership at request time | `Available` task filter calls `findEmployeesByRole(candidateRoleId)` for every task in the list | Cache role membership or denormalize candidate set onto the task at creation time | > 5 pool tasks visible simultaneously |
| Full form schema in task list response | Returning complete `form_schema` (including all field definitions) in the list endpoint wastes bandwidth | List endpoint returns only `{ hasWorkflow: bool, actionCount: int, processName: string }`; detail endpoint returns full schema | First page load with 50 tasks |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Not verifying `organizationId` on CompleteTaskNode | User from Org A completes a task belonging to Org B's process instance | `CompleteTaskNodeHandler` must verify `processInstance.organizationId == currentUser.organizationId` |
| Allowing any authenticated user to claim a pool task | User not in the candidate role claims the task | `ClaimTaskHandler` must validate `currentEmployee` has the `candidateRoleId` or belongs to `candidateDepartmentId` before allowing claim |
| Exposing process variables in task detail API | `ProcessInstance.variables` may contain `_task_creator_id`, internal routing keys, or data from other stages | Task detail API returns only `form_schema` (definition) and `{ processName, stageName }` context; never expose raw `variables` array to end users |
| Condition expressions containing user-controlled input | If a process designer can define expressions referencing process variables that users fill, a malicious expression could access PHP object methods via ExpressionLanguage | Symfony ExpressionLanguage is sandboxed by default but can be extended with functions — never register functions that allow file access or system calls; validate expressions at publish time against a whitelist of allowed operators and variable names |
| `form_schema` trusting client-submitted `action` key without validation | Frontend sends `action: "approve"` but the transition was renamed `action: "approved"` in designer; backend falls through to default transition silently | Validate the submitted `action` key exists in `form_schema.actions[*].key` before processing |

---

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Showing action buttons before shared fields are filled | User clicks "Approve" without entering required shared fields; form shows inline errors after click | Validate shared fields first; disable action buttons until shared fields pass validation |
| Claiming a task without seeing its content first | User claims and then realizes they cannot complete it; must unclaim, wasting a round-trip | Show full task detail (including form preview) before the claim button is active; the assignment spec already decides this correctly — reinforce it |
| No visual distinction between standalone tasks and workflow tasks | User doesn't know why there are action buttons on some tasks and not others | Task card must show process badge (process name + stage fraction like "3/7") for workflow tasks; standalone tasks show no badge |
| Action dialog requires clicking twice (select action → open dialog → submit) | Friction for simple linear tasks that have only one action ("Complete") | When `form_schema.actions` has exactly 1 entry and 0 action-specific fields, collapse to a single "Complete" button; no dialog needed |
| "Available" tab always visible even when user has no eligible pool tasks | Empty state confuses users who don't belong to any pool | Show "Available" tab only when `candidateRoleId IN myRoles OR candidateDepartmentId = myDeptId` — hide tab if count is 0 |

---

## "Looks Done But Isn't" Checklist

- [ ] **Form schema embedding:** Task detail returns `form_schema` — verify it was written at creation time, not fetched live from process definition
- [ ] **Variable namespacing:** Submit a task with field `comment`, then submit a later task with the same field name — verify first value is preserved under a namespaced key
- [ ] **Required field backend validation:** POST to `/tasks/{id}/complete` with empty `formData` — verify HTTP 422 with field-level errors, not 500
- [ ] **Claim race condition:** Two simultaneous claim requests for the same task — verify exactly one succeeds with 200 and the other gets 409
- [ ] **Token-scoped link resolution:** Process that loops back to the same task node — verify second activation creates a new task, not updates the first
- [ ] **Organization boundary on complete:** Authenticated user from Org A calls complete on Org B's task — verify 403, not 200
- [ ] **ExpressionEvaluator undefined variable:** XOR gateway condition references a variable not in `ProcessInstance.variables` — verify warning logged and default transition taken, not silent wrong-path routing
- [ ] **Process cancellation propagates to tasks:** Cancel a process instance with active task tokens — verify linked tasks transition to a cancelled/closed state

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Form schema snapshot not stored (live reference) | HIGH | Backfill by replaying process events to reconstruct form schema at creation time; add snapshot column + migration; update all affected tasks |
| Variable key collisions already in production data | HIGH | Audit `ProcessInstance.variables` for all running instances; rename colliding keys; update downstream conditions in process definitions; no automated fix |
| Double-claim race condition reached production | MEDIUM | Add database-level unique partial index on claimed tasks; write a data repair script to detect tasks with ambiguous state; notify affected users |
| Silent wrong-path routing via undefined variable | MEDIUM | Add monitoring query: XOR gateways that always take the same path across many instances → flag for manual review; add variable validation at publish time going forward |
| Token-task link broken by loop (wrong task reused) | HIGH | Identify affected process instances where `WorkflowTaskLink.tokenId` doesn't match current active token; manually create correct links; reprocess affected tokens |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Form schema snapshot vs. live reference | Session 2 — build `form_schema` embedding in `OnTaskNodeActivated` | Task detail API returns schema that does not change when process definition is republished |
| Variable key collisions | Session 1 — establish namespaced merge strategy in `VariablesMergedEvent` apply | Submit identical field names from two different task nodes; assert both keys preserved |
| ExpressionEvaluator silent errors | Session 1 — ExpressionEvaluator + gateway integration | Unit test: undefined variable in condition → warning logged, default path taken |
| Race condition on claim | Session covering claim/unclaim handlers | Integration test: two concurrent claim requests → one 409 |
| Token-task link breaks on loops | Session 2 — change link resolution to token-scoped | Integration test: process visits same task node twice → two separate tasks created |
| Frontend-only form validation | Session 3 — `FormSchemaValidator` service in CompleteTaskNode handler | API test: POST empty `formData` with required fields → 422 |
| RBAC boundary on CompleteTaskNode | Session 3 — add org boundary check in handler | API test: cross-org complete → 403 |
| N+1 on task list with process context | Session 4 — optimize ListTasksHandler join | Load test: 50 tasks page; assert query count = 1, not 51 |

---

## Sources

- Procivo codebase: `backend/src/Workflow/Domain/Service/WorkflowEngine.php` — XOR/parallel/inclusive gateway execution patterns
- Procivo codebase: `backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php` — current link resolution strategy (pitfalls 5 identified)
- Procivo codebase: `backend/src/Workflow/Domain/Service/ExpressionEvaluator.php` — SyntaxError-only catch (pitfall 3 identified)
- Procivo codebase: `backend/src/TaskManager/Domain/Entity/Task.php` — claim/unclaim domain logic (pitfall 4 identified)
- `docs/WORKFLOW_TASKS_INTEGRATION_PLAN.md` — architecture decision: forms per action, form schema structure
- `docs/TASK_ASSIGNMENT_SPEC.md` — competitor analysis (Camunda, Appian, Bonita, Bitrix24, BPMN 2.0 spec), claim design decisions
- BPMN 2.0 specification — `humanPerformer` / `potentialOwner` semantics inform variable namespacing and pool task design
- Camunda platform known issues — expression evaluation with undefined variables; token-scoped task links (training knowledge, HIGH confidence for Camunda 7)
- Activiti/Flowable community — variable scoping between tasks is the #1 source of production bugs in custom BPMN engines (training knowledge, MEDIUM confidence)

---
*Pitfalls research for: BPM Workflow-Task Integration (Procivo)*
*Researched: 2026-02-28*
