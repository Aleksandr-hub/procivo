# Phase 3: Completion and Claim APIs - Research

**Researched:** 2026-02-28
**Domain:** Task completion via workflow action execution, pool task claim/unclaim with pessimistic locking, form validation, variable merging, gateway routing
**Confidence:** HIGH

## Summary

Phase 3 builds two API capabilities on top of the infrastructure established in Phases 1-2: (1) a task completion endpoint that accepts `{ action, formData }`, validates form data against the task's snapshotted `form_schema`, merges validated data into process variables, and advances the workflow engine token through the selected action's transition; and (2) claim/unclaim endpoints for pool tasks with pessimistic database locking to prevent double-claim race conditions.

The codebase is remarkably well-prepared. The `ExecuteTaskActionHandler` already implements 80% of the completion flow -- it looks up the `WorkflowTaskLink`, loads the `ProcessInstance` and `ProcessGraph`, collects form fields via `FormFieldCollector`, validates required fields (inline), merges variables into `ProcessInstance`, and calls `WorkflowEngine::executeAction()` to advance the token. The `ClaimTaskHandler` and `UnclaimTaskHandler` already implement the full claim/unclaim domain logic including eligibility validation via `OrganizationQueryPort`. The Task entity already has `claim()` and `unclaim()` domain methods with proper guard checks and domain events. The controller already has `/claim` and `/unclaim` routes.

The main gaps are: (1) The completion endpoint uses `ExecuteTaskActionCommand` with inline required-only validation instead of the full `FormSchemaValidator` with type, constraint, regex, and dependency validation built in Phase 1. (2) The claim handler does NOT use pessimistic locking -- it loads the task with a normal `findById()`, checks `assigneeId === null`, then saves, creating a TOCTOU race window. (3) The task's status is not transitioned when a workflow action completes it -- the task stays in its current Symfony Workflow state. (4) There is no dedicated `/complete` endpoint -- the existing `/execute-action` endpoint semantically does the same thing but needs to be renamed or aliased per requirements.

**Primary recommendation:** Upgrade `ExecuteTaskActionHandler` to use `FormSchemaValidator` instead of inline validation, add `findByIdForUpdate()` with `LockMode::PESSIMISTIC_WRITE` to `TaskRepositoryInterface` for the claim path, transition the task status to `done` via Symfony Workflow after successful action execution, and either rename the existing `/execute-action` route to `/complete` or create an alias.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| COMP-01 | API POST /api/v1/tasks/{id}/complete accepts { action, formData } | `TaskController::executeAction()` already exists at `/{taskId}/execute-action` with `{ action_key, form_data }`. Needs route rename/alias to `/complete` and field name alignment (`action` vs `action_key`). `ExecuteTaskActionCommand` + `ExecuteTaskActionHandler` implement the full flow. |
| COMP-04 | After merge, workflow engine advances -- token moves to next node via selected action's transition | Already implemented in `ExecuteTaskActionHandler`: calls `$this->engine->executeAction($instance, $tokenId, $graph, $command->actionKey)` which finds the transition by actionKey, moves token to target node, and recursively advances via `advanceToken()`. If next node is XOR gateway, `handleExclusiveGateway()` evaluates conditions against updated `$instance->variables()`. |
| ASGN-05 | API POST /api/v1/tasks/{id}/claim -- employee claims pool task with pessimistic locking | `TaskController::claim()` route exists. `ClaimTaskCommand` + `ClaimTaskHandler` implement eligibility checks via `OrganizationQueryPort`. **Missing: pessimistic locking.** Current `findById()` does a normal SELECT -- needs `findByIdForUpdate()` with `LockMode::PESSIMISTIC_WRITE` inside a transaction to prevent double-claim. |
| ASGN-06 | API POST /api/v1/tasks/{id}/unclaim -- employee returns task to pool | `TaskController::unclaim()` route exists. `UnclaimTaskCommand` + `UnclaimTaskHandler` verify task is pool task and current claimant matches. `Task::unclaim()` sets `assigneeId = null` and records `TaskUnclaimedEvent`. Functionally complete. Needs pessimistic lock for consistency with claim path. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| doctrine/orm | ^3.6 | Entity persistence, pessimistic locking via `LockMode::PESSIMISTIC_WRITE` | Already installed. Provides `EntityManager::find($class, $id, LockMode::PESSIMISTIC_WRITE)` for row-level locking |
| symfony/messenger | 8.0.* | CQRS command/query/event buses | Already installed. ExecuteTaskActionCommand dispatched via command.bus |
| symfony/workflow | 8.0.* | Task state machine (draft/open/in_progress/review/done) | Already installed. `workflow_complete` transition allows direct to `done` from any active state |
| symfony/expression-language | 8.0.* | Gateway condition evaluation | Already installed. Used by ExpressionEvaluator in XOR gateway handling |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHPUnit | ^13.0 | Unit testing | Test completion flow, claim concurrency, validation integration |
| psr/log | 3.0 | Structured logging via LoggerInterface | Warning logs for expression evaluation failures at gateways |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Pessimistic DB lock for claim | Optimistic lock (version column) | Pessimistic is simpler for this use case -- claim is a short-lived operation. Optimistic requires adding a version column, handling `OptimisticLockException` with retry logic, and the Task entity doesn't have a version field. **Recommendation: pessimistic lock** |
| Renaming `/execute-action` to `/complete` | Keeping both routes | Two routes for the same operation adds confusion. **Recommendation: rename to `/complete`**, update frontend references |
| Inline validation upgrade | Creating a new handler | The existing `ExecuteTaskActionHandler` does everything needed. Just replace inline validation with `FormSchemaValidator` call. No need for a separate handler |

## Architecture Patterns

### Current Codebase Structure (affected files)
```
backend/src/
├── Workflow/
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── ExecuteTaskAction/
│   │   │   │   ├── ExecuteTaskActionCommand.php    # HAS: taskId, actionKey, formData
│   │   │   │   └── ExecuteTaskActionHandler.php    # MODIFY: replace inline validation with FormSchemaValidator
│   │   │   └── CompleteTaskNode/
│   │   │       └── CompleteTaskNodeHandler.php      # EXISTS: lower-level token resume (no form logic)
│   │   └── Service/
│   │       └── FormFieldCollector.php               # EXISTS: collects fields for validation
│   ├── Domain/
│   │   ├── Service/
│   │   │   ├── FormSchemaValidator.php              # EXISTS (Phase 1): full type/constraint/dependency validation
│   │   │   └── WorkflowEngine.php                   # EXISTS: executeAction(), handleExclusiveGateway()
│   │   └── Entity/
│   │       ├── ProcessInstance.php                   # EXISTS: mergeVariables() with namespacing
│   │       └── WorkflowTaskLink.php                  # EXISTS: markCompleted()
│   └── Infrastructure/
│       └── Repository/
│           └── EventSourcedProcessInstanceRepository.php  # EXISTS: event-sourced save/load
├── TaskManager/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── Task.php                              # EXISTS: claim(), unclaim(), isPoolTask(), formSchema()
│   │   ├── Exception/
│   │   │   └── TaskClaimException.php                # EXISTS: notAPoolTask, alreadyClaimed, notEligible
│   │   └── Repository/
│   │       └── TaskRepositoryInterface.php           # MODIFY: add findByIdForUpdate()
│   ├── Application/
│   │   └── Command/
│   │       ├── ClaimTask/
│   │       │   └── ClaimTaskHandler.php              # MODIFY: use findByIdForUpdate() + wrapInTransaction
│   │       └── UnclaimTask/
│   │           └── UnclaimTaskHandler.php             # MODIFY: use findByIdForUpdate() + wrapInTransaction
│   ├── Infrastructure/
│   │   └── Repository/
│   │       └── DoctrineTaskRepository.php            # MODIFY: implement findByIdForUpdate()
│   └── Presentation/
│       └── Controller/
│           └── TaskController.php                    # MODIFY: rename /execute-action to /complete, align field names
```

### Pattern 1: Pessimistic Locking for Claim/Unclaim
**What:** Use `EntityManager::find()` with `LockMode::PESSIMISTIC_WRITE` inside a transaction to prevent concurrent claims.

**When to use:** Any operation where two concurrent requests could read the same state and both attempt to modify it (double-claim race condition).

**Design:**
```php
// TaskRepositoryInterface -- add new method
public function findByIdForUpdate(TaskId $id): ?Task;

// DoctrineTaskRepository -- implementation
public function findByIdForUpdate(TaskId $id): ?Task
{
    return $this->entityManager->find(
        Task::class,
        $id->value(),
        LockMode::PESSIMISTIC_WRITE,
    );
}

// ClaimTaskHandler -- wrap in transaction
public function __invoke(ClaimTaskCommand $command): void
{
    $this->entityManager->wrapInTransaction(function () use ($command): void {
        $task = $this->taskRepository->findByIdForUpdate(
            TaskId::fromString($command->taskId),
        );

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        // ... eligibility checks ...

        $task->claim($command->employeeId);
        // No explicit flush needed -- wrapInTransaction handles it
    });
}
```

**Why this pattern:**
- `PESSIMISTIC_WRITE` issues `SELECT ... FOR UPDATE`, blocking concurrent reads of the same row
- `wrapInTransaction` ensures the lock is held for the minimum necessary time
- The TOCTOU window (check assigneeId=null, then claim) is eliminated because the second request blocks on the SELECT
- PostgreSQL `FOR UPDATE` is well-supported and efficient for single-row locks
- No schema changes needed (unlike optimistic locking which requires a version column)

**Important:** Doctrine's `EntityManager::wrapInTransaction()` calls `flush()` before commit and closes the EntityManager on exception. The handler should NOT call `$this->taskRepository->save()` inside the transaction -- the `wrapInTransaction` flush handles it.

### Pattern 2: Full FormSchemaValidator Integration in ExecuteTaskActionHandler
**What:** Replace the inline `validateFormData()` method with a call to `FormSchemaValidator::validate()` which provides type checking, numeric constraints, regex patterns, and field dependency resolution.

**When to use:** Every time form data is submitted with a task action.

**Design:**
```php
// ExecuteTaskActionHandler -- inject FormSchemaValidator
public function __construct(
    // ... existing deps ...
    private FormSchemaValidator $formSchemaValidator,
) {}

public function __invoke(ExecuteTaskActionCommand $command): void
{
    // ... existing link/instance/graph loading ...

    $allFields = $this->fieldCollector->collectForValidation($graph, $nodeId, $command->actionKey);

    // Replace inline validation with full validator
    $errors = $this->formSchemaValidator->validate($allFields, $command->formData);
    if ([] !== $errors) {
        throw FormValidationException::validationFailed($errors);
    }

    // ... rest of the flow (merge variables, execute action, save) ...
}
```

**Why this pattern:**
- `FormSchemaValidator` was built in Phase 1 specifically for this purpose
- It handles: required (with dependency resolution), type validation (text/number/date/select/checkbox/textarea/employee), numeric min/max, string minLength/maxLength, regex patterns, and cascading field dependencies
- The existing inline `validateFormData()` only checks required fields -- insufficient for production quality
- The validator returns structured `FieldValidationError` objects that serialize to informative API responses

### Pattern 3: Task Status Transition on Workflow Completion
**What:** When `ExecuteTaskActionHandler` completes successfully, transition the Task entity's status to `done` via the Symfony Workflow state machine's `workflow_complete` transition.

**When to use:** After the workflow engine advances the token (the task's workflow role is fulfilled).

**Design:**
```php
// In ExecuteTaskActionHandler, after engine.executeAction:
// Transition the TaskManager task to done
$this->commandBus->dispatch(new TransitionTaskCommand(
    taskId: $command->taskId,
    transition: 'workflow_complete',
));
```

**Important considerations:**
- The `workflow_complete` transition is already defined in `workflow.yaml` with `from: [draft, open, in_progress, review]` and `to: done` with `metadata: { internal: true }`
- This transition is specifically designed for workflow-driven completion -- it bypasses the normal task lifecycle (draft -> open -> in_progress -> review -> done)
- The handler should dispatch `TransitionTaskCommand` AFTER successful workflow action execution, not before
- If the transition fails (e.g., task already in `done` or `cancelled` state), it should log a warning but not fail the workflow completion

### Anti-Patterns to Avoid
- **Implementing claim lock at the application level (PHP mutex/semaphore):** PHP processes are isolated. Application-level locks don't work across concurrent HTTP requests. Always use database-level locking.
- **Using optimistic lock for claim without version column:** Task entity has no version field. Adding one for a single use case is overengineering. Pessimistic lock is simpler and more appropriate for short-lived operations.
- **Calling flush() inside wrapInTransaction:** `EntityManager::wrapInTransaction()` already calls `flush()` before commit. Double-flushing can cause unexpected behavior.
- **Validating formData against the process definition instead of the task's snapshot:** The Task's `formSchema` is the authoritative source (snapshotted at creation). Validating against the live process definition would create schema drift issues if the definition was updated.
- **Creating a separate `/complete` handler from scratch:** The existing `ExecuteTaskActionHandler` implements the full completion flow. Duplicating it creates maintenance burden. Enhance it instead.
- **Skipping task status transition on workflow completion:** If the task stays in `draft`/`open`/`in_progress` after the workflow advances, the task list shows stale status. Users see "in progress" tasks that are actually done.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Form data validation | New inline validator in handler | `FormSchemaValidator` (Phase 1) | Already handles 7 field types, 5 constraint rules, cascading dependencies. 335 lines of tested code |
| Concurrent claim prevention | PHP-level mutex, Redis lock | `Doctrine LockMode::PESSIMISTIC_WRITE` | Database-native `SELECT FOR UPDATE`. Works across all PHP processes, no extra infrastructure |
| Expression evaluation for gateways | Custom condition parser | `ExpressionEvaluator` + Symfony ExpressionLanguage | Already handles all operators, structured error logging, `\Throwable` catch |
| Variable namespacing | Custom merge function | `ProcessInstance::mergeVariables()` (Phase 1) | Already implements dual-layer: namespaced `stages.{nodeId}.{actionKey}` + flat aliases |
| Task state transition | Direct status string assignment | Symfony Workflow `workflow_complete` transition | State machine enforces valid transitions, fires audit events |

**Key insight:** Phase 3 is primarily a **wiring and hardening** phase. The individual components (validator, engine, claim logic, variable merge) already exist from Phases 1-2. This phase connects them into the `/complete` and `/claim` API endpoints with proper error handling and concurrency control.

## Common Pitfalls

### Pitfall 1: Double-Claim Race Condition Without Locking
**What goes wrong:** Two users simultaneously POST to `/claim` for the same pool task. Both read `assigneeId = null`, both pass the eligibility check, both write their own employeeId. Last writer wins -- first claimant's work is silently overwritten.
**Why it happens:** `findById()` issues a normal `SELECT` without locks. PostgreSQL's default isolation level (Read Committed) allows both transactions to see `assigneeId = null` simultaneously.
**How to avoid:** Use `findByIdForUpdate()` with `LockMode::PESSIMISTIC_WRITE`. The first request acquires a `FOR UPDATE` lock; the second blocks until the first commits. When it resumes, it re-reads the row and sees `assigneeId` is already set, triggering `TaskClaimException::alreadyClaimed()`.
**Warning signs:** Intermittent "task already claimed" errors in production, or worse: silent double-assignment.

### Pitfall 2: EntityManager Closed After Transaction Rollback
**What goes wrong:** After `wrapInTransaction` catches an exception and rolls back, the EntityManager is closed. Subsequent Doctrine operations in the same request fail with "EntityManager is closed."
**Why it happens:** `wrapInTransaction()` closes the EntityManager on exception to prevent inconsistent state.
**How to avoid:** Don't catch and swallow exceptions from `wrapInTransaction`. Let them propagate to the controller/error handler. The HTTP response will be an error, and the next request gets a fresh EntityManager. For claim, this is fine -- the error response tells the user the claim failed.
**Warning signs:** "EntityManager is closed" errors in logs after failed claim attempts.

### Pitfall 3: Validation Against ProcessGraph Instead of Task's form_schema
**What goes wrong:** The handler validates formData by collecting fields from the ProcessGraph (live process definition). If the process definition was updated since the task was created, the validation schema doesn't match what the user sees.
**Why it happens:** `FormFieldCollector::collectForValidation()` reads from the ProcessGraph, which comes from the version snapshot. If the version hasn't changed, it's consistent. But if the implementation accidentally loads the latest version instead of the instance's version, schemas drift.
**How to avoid:** Always load the ProcessDefinitionVersion via `$instance->versionId()` (the version the process was started with), never the latest published version. The current code already does this correctly in `ExecuteTaskActionHandler` line 55: `$version = $this->versionRepository->findById($instance->versionId())`. Alternatively, validate against `Task::formSchema()` directly -- it was snapshotted at creation time. **Recommendation: keep validating from ProcessGraph via FormFieldCollector** for Phase 3, because the graph-based validation also handles `from_variable` assignee field injection. The Task's `formSchema` is for frontend display; the ProcessGraph-based collection is for backend validation.
**Warning signs:** Users submit valid form data but get validation errors, or vice versa.

### Pitfall 4: Task Status Not Updated After Workflow Completion
**What goes wrong:** The workflow engine advances the token (process moves forward), but the Task entity remains in `in_progress` or `open` status. The task list shows the task as active when it's actually completed from the workflow perspective.
**Why it happens:** `ExecuteTaskActionHandler` only marks the `WorkflowTaskLink` as completed (`$link->markCompleted()`) but doesn't transition the Task's Symfony Workflow status.
**How to avoid:** After successful `engine->executeAction()`, dispatch a `TransitionTaskCommand` with `workflow_complete` transition. This moves the task to `done` state. The `workflow_complete` transition is already configured in `workflow.yaml` with `from: [draft, open, in_progress, review]`.
**Warning signs:** Tasks with `completedAt` on their `WorkflowTaskLink` but still showing status `in_progress` in the task list.

### Pitfall 5: Gateway Evaluation Before Variable Merge
**What goes wrong:** If the order of operations is wrong (engine advances BEFORE variables are merged), the XOR gateway evaluates conditions against stale variables. The routing decision is based on data from previous stages, not the just-submitted form data.
**Why it happens:** Incorrect ordering of `mergeVariables()` and `engine->executeAction()` in the handler.
**How to avoid:** The current code has the correct order: line 68 merges variables, line 72 executes the action. The merge happens first, so when `handleExclusiveGateway` reads `$instance->variables()`, it sees the updated values. **Do not reorder these operations.**
**Warning signs:** Gateway always takes the same branch regardless of form data. Verify with a test: submit different values and assert different gateway outcomes.

### Pitfall 6: Unclaim Without Authorization Check
**What goes wrong:** Any user can unclaim any other user's task by submitting the correct `employeeId`.
**Why it happens:** The `UnclaimTaskHandler` checks `$task->assigneeId() !== $command->employeeId` but doesn't verify that the requesting user IS the employeeId they claim to be.
**How to avoid:** The `employeeId` in the command should come from the authenticated user's security context, not from the request body. The controller should extract the current user's employee ID from the JWT/security context and pass it to the command. This is an authorization concern, not a domain concern.
**Warning signs:** API accepts arbitrary `employee_id` values in the request body for claim/unclaim.

## Code Examples

Verified patterns from codebase inspection:

### Current: ExecuteTaskActionHandler (existing completion flow)
```php
// Source: backend/src/Workflow/Application/Command/ExecuteTaskAction/ExecuteTaskActionHandler.php
// This handler already implements the full completion flow:
// 1. Look up WorkflowTaskLink by taskId
// 2. Load ProcessInstance and ProcessGraph
// 3. Collect form fields via FormFieldCollector
// 4. Validate formData (currently inline required-only check)
// 5. Merge variables into ProcessInstance
// 6. Call engine->executeAction() to advance token
// 7. Save ProcessInstance and mark link completed

// Line 65-66: Field collection
$allFields = $this->fieldCollector->collectForValidation($graph, $nodeId, $command->actionKey);
$this->validateFormData($allFields, $command->formData);

// Line 68-69: Variable merge (BEFORE engine execution)
if ([] !== $command->formData) {
    $instance->mergeVariables($nodeId, $command->actionKey, $command->formData);
}

// Line 72-73: Engine execution (advances token, triggers gateway evaluation)
$this->engine->executeAction($instance, $tokenId, $graph, $command->actionKey);
$this->instanceRepository->save($instance);
```

### Current: WorkflowEngine::executeAction() (token advancement)
```php
// Source: backend/src/Workflow/Domain/Service/WorkflowEngine.php:49-72
public function executeAction(ProcessInstance $instance, TokenId $tokenId, ProcessGraph $graph, string $actionKey): void
{
    $token = $instance->getToken($tokenId);
    if (!$token->isWaiting()) {
        throw WorkflowExecutionException::invalidTransition('Token is not waiting at a task node');
    }

    $nodeId = $token->nodeId()->value();
    $transition = $graph->findOutgoingTransitionByActionKey($nodeId, $actionKey);

    // Fallback: if no transition matches actionKey, use single outgoing transition
    if (null === $transition) {
        $outgoing = $graph->outgoingTransitions($nodeId);
        if (1 === \count($outgoing)) {
            $transition = $outgoing[0];
        } else {
            throw WorkflowExecutionException::invalidTransition(/*...*/);
        }
    }

    $targetNodeId = NodeId::fromString($transition['target_node_id']);
    $instance->moveToken($tokenId, $token->nodeId(), $targetNodeId, $transition['id']);

    // Recursively advance -- if target is XOR gateway, it evaluates conditions
    $this->advanceToken($instance, $tokenId, $graph);
}
```

### Current: ClaimTaskHandler (without locking)
```php
// Source: backend/src/TaskManager/Application/Command/ClaimTask/ClaimTaskHandler.php
public function __invoke(ClaimTaskCommand $command): void
{
    // BUG: Normal SELECT without lock -- TOCTOU race condition
    $task = $this->taskRepository->findById(TaskId::fromString($command->taskId));

    if (null === $task) {
        throw TaskNotFoundException::withId($command->taskId);
    }

    if (!$task->isPoolTask()) {
        throw TaskClaimException::notAPoolTask($command->taskId);
    }

    // BUG: This check can pass for two concurrent requests
    if (null !== $task->assigneeId()) {
        throw TaskClaimException::alreadyClaimed($command->taskId);
    }

    $this->validateEligibility($command->employeeId, $task);

    $task->claim($command->employeeId);
    $this->taskRepository->save($task);
}
```

### Current: Task::claim() domain method
```php
// Source: backend/src/TaskManager/Domain/Entity/Task.php:104-117
public function claim(string $employeeId): void
{
    if (!$this->isPoolTask()) {
        throw TaskClaimException::notAPoolTask($this->id);
    }

    if (null !== $this->assigneeId) {
        throw TaskClaimException::alreadyClaimed($this->id);
    }

    $this->assigneeId = $employeeId;
    $this->updatedAt = new \DateTimeImmutable();
    $this->recordEvent(new TaskClaimedEvent($this->id, $employeeId));
}
```

### Current: Symfony Workflow `workflow_complete` transition
```yaml
# Source: backend/config/packages/workflow.yaml
workflow_complete:
    from: [draft, open, in_progress, review]
    to: done
    metadata:
        internal: true
```

### Proposed: findByIdForUpdate with pessimistic lock
```php
// DoctrineTaskRepository
use Doctrine\DBAL\LockMode;

public function findByIdForUpdate(TaskId $id): ?Task
{
    return $this->entityManager->find(
        Task::class,
        $id->value(),
        LockMode::PESSIMISTIC_WRITE,
    );
}
```

### Proposed: ClaimTaskHandler with transaction + lock
```php
public function __construct(
    private TaskRepositoryInterface $taskRepository,
    private OrganizationQueryPort $organizationQueryPort,
    private EntityManagerInterface $entityManager,
) {}

public function __invoke(ClaimTaskCommand $command): void
{
    $this->entityManager->wrapInTransaction(function () use ($command): void {
        $task = $this->taskRepository->findByIdForUpdate(
            TaskId::fromString($command->taskId),
        );

        if (null === $task) {
            throw TaskNotFoundException::withId($command->taskId);
        }

        if (!$task->isPoolTask()) {
            throw TaskClaimException::notAPoolTask($command->taskId);
        }

        if (null !== $task->assigneeId()) {
            throw TaskClaimException::alreadyClaimed($command->taskId);
        }

        $this->validateEligibility($command->employeeId, $task);

        $task->claim($command->employeeId);
        // wrapInTransaction calls flush() before commit -- no explicit save needed
    });
}
```

### Proposed: ExecuteTaskActionHandler with FormSchemaValidator
```php
public function __construct(
    // ... existing deps ...
    private FormSchemaValidator $formSchemaValidator,
) {}

public function __invoke(ExecuteTaskActionCommand $command): void
{
    // ... existing link/instance/graph loading (lines 34-64) ...

    $allFields = $this->fieldCollector->collectForValidation($graph, $nodeId, $command->actionKey);

    // NEW: Full validation replaces inline required-only check
    $errors = $this->formSchemaValidator->validate($allFields, $command->formData);
    if ([] !== $errors) {
        throw FormValidationException::validationFailed($errors);
    }

    // ... rest unchanged: merge variables, execute action, save ...
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Required-only inline validation in handler | FormSchemaValidator with full type/constraint/dependency validation | Phase 1 (this milestone) | Form data validated properly before merging into process variables |
| Normal SELECT for task claim | `SELECT ... FOR UPDATE` via `LockMode::PESSIMISTIC_WRITE` | This phase | Eliminates double-claim race condition |
| Task status unchanged after workflow completion | `workflow_complete` transition to `done` | This phase | Task list reflects actual completion state |
| `/execute-action` endpoint name | `/complete` endpoint name | This phase | Aligns with requirements spec (COMP-01) |

## Open Questions

1. **Should `/complete` replace `/execute-action` or coexist?**
   - What we know: Requirements specify `POST /api/v1/tasks/{id}/complete`. The existing route is `/{taskId}/execute-action`. Frontend code references `executeAction`.
   - What's unclear: Should the old route be removed immediately or deprecated?
   - Recommendation: **Rename to `/complete`** and update frontend references. The old route is only used in `task.api.ts`. Since this is a pet project with no external consumers, a clean rename is simpler than maintaining two routes. Also align the request field names: `action_key` -> `action`, `form_data` -> `formData` (or keep snake_case for consistency with other endpoints).

2. **Should ClaimTaskHandler inject EntityManagerInterface directly?**
   - What we know: The handler currently depends only on `TaskRepositoryInterface` and `OrganizationQueryPort`. Adding `EntityManagerInterface` breaks the clean architecture pattern (infrastructure dependency in application layer).
   - What's unclear: How to wrap the repository call in a transaction without leaking infrastructure concerns.
   - Recommendation: Two options: (A) Inject `EntityManagerInterface` directly -- pragmatic, handler is already coupled to Doctrine implicitly. (B) Add a `transactional()` method to `TaskRepositoryInterface` that accepts a callable -- abstraction over transaction management. **Recommendation: Option A** for simplicity. The handler is already in the Application layer and the EntityManagerInterface is a well-known abstraction. The Doctrine dependency is already present in the infrastructure layer anyway. Alternatively, the `findByIdForUpdate` + `save` pattern can be kept if the repository's `save` is modified to be transaction-aware.

3. **Should the task's status be transitioned to `done` inside ExecuteTaskActionHandler or as a separate event?**
   - What we know: The handler marks `WorkflowTaskLink.completedAt` but doesn't touch the Task's status. The Symfony Workflow state machine has a `workflow_complete` transition.
   - What's unclear: Should the transition be synchronous (in the same handler) or asynchronous (via event bus)?
   - Recommendation: **Synchronous** -- dispatch `TransitionTaskCommand` from inside `ExecuteTaskActionHandler` after successful action execution. This ensures the task status is consistent by the time the API response is sent. An async approach would create a window where the task appears active despite being completed.

4. **Should unclaim also use pessimistic locking?**
   - What we know: Unclaim is less susceptible to race conditions because only the current assignee can unclaim. Concurrent unclaim attempts from the same user are unlikely.
   - What's unclear: Is there a scenario where concurrent claim+unclaim creates issues?
   - Recommendation: **Yes, use the same locking pattern** for consistency. The performance impact is negligible (single row lock for a short transaction), and it prevents edge cases where claim and unclaim happen simultaneously for the same task.

## Sources

### Primary (HIGH confidence)
- Codebase inspection: ExecuteTaskActionHandler.php, ClaimTaskHandler.php, UnclaimTaskHandler.php, Task.php, TaskController.php, WorkflowEngine.php, ProcessInstance.php, FormSchemaValidator.php, FormFieldCollector.php, FormSchemaBuilder.php, WorkflowTaskLink.php, DoctrineTaskRepository.php, TaskRepositoryInterface.php, TaskClaimException.php, FormValidationException.php, OrganizationQueryPort.php, workflow.yaml, services.yaml, Task.orm.xml -- all read directly from `/Users/leleka/Projects/procivo/backend/src/`
- Phase 1 research (01-RESEARCH.md) -- FormSchemaValidator architecture, variable namespacing patterns
- Phase 2 research (02-RESEARCH.md) -- FormSchemaBuilder, assignment resolution, form_schema snapshot pattern
- [Doctrine ORM 3.6: Transactions and Concurrency](https://www.doctrine-project.org/projects/doctrine-orm/en/3.6/reference/transactions-and-concurrency.html) -- `LockMode::PESSIMISTIC_WRITE`, `wrapInTransaction()`, `TransactionRequiredException`

### Secondary (MEDIUM confidence)
- [TASK_ASSIGNMENT_SPEC.md](docs/TASK_ASSIGNMENT_SPEC.md) -- claim/unclaim mechanism design, pool task lifecycle
- [WORKFLOW_TASKS_INTEGRATION_PLAN.md](docs/WORKFLOW_TASKS_INTEGRATION_PLAN.md) -- data flow: action submission -> variables merge -> token advance -> gateway evaluation

### Tertiary (LOW confidence)
- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all libraries already installed, Doctrine pessimistic locking verified via official docs
- Architecture: HIGH -- existing handler implements 80% of completion flow. Claim/unclaim handlers exist with domain logic. Modifications are surgical: inject validator, add lock, rename route
- Pitfalls: HIGH -- race condition in claim identified through code review. Variable merge ordering verified as correct. Gateway evaluation flow traced through WorkflowEngine source
- Concurrency: HIGH -- Doctrine `LockMode::PESSIMISTIC_WRITE` with PostgreSQL `FOR UPDATE` is well-established pattern, verified in official docs

**Research date:** 2026-02-28
**Valid until:** 2026-03-28 (stable domain, internal codebase patterns, no fast-moving dependencies)
