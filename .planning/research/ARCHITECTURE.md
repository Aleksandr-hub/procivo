# Architecture Research

**Domain:** BPM Workflow-Task Integration (Procivo)
**Researched:** 2026-02-28
**Confidence:** HIGH — based on direct codebase analysis + established BPM industry patterns

---

## Standard Architecture

### System Overview

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                        Presentation Layer                                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────────┐   │
│  │  TaskController  │  │  ProcessInstance  │  │  ProcessDefinition       │   │
│  │  /tasks/{id}     │  │  Controller       │  │  Controller              │   │
│  │  /tasks/{id}/    │  │                   │  │                          │   │
│  │  complete        │  │                   │  │                          │   │
│  └────────┬─────────┘  └────────┬──────────┘  └──────────────┬───────────┘  │
└───────────┼─────────────────────┼─────────────────────────────┼──────────────┘
            │  Commands/Queries   │                             │
┌───────────┼─────────────────────┼─────────────────────────────┼──────────────┐
│           │    Application Layer │                             │              │
│  ┌────────▼──────────────────┐  │  ┌──────────────────────────▼───────────┐  │
│  │  ExecuteTaskActionHandler  │  │  │  GetTaskWorkflowContextHandler        │  │
│  │  (command.bus)             │  │  │  (query.bus)                         │  │
│  │  • Validates form data     │  │  │  • Returns form_schema               │  │
│  │  • Merges variables        │  │  │  • Returns process context           │  │
│  │  • Calls WorkflowEngine    │  │  └──────────────────────────────────────┘  │
│  └────────┬───────────────────┘  │                                            │
│           │                      │  ┌──────────────────────────────────────┐  │
│  ┌────────▼───────────────────┐  │  │  OnTaskNodeActivated (event.bus)      │  │
│  │  AssignmentResolver         │  │  │  • Builds form_schema from config    │  │
│  │  (Application Service)      │  │  │  • Resolves assignment strategy      │  │
│  │  • Resolves strategy →      │  │  │  • Dispatches CreateTaskCommand       │  │
│  │    assigneeId + candidates  │  │  └──────────────────────────────────────┘  │
│  └────────┬───────────────────┘  │                                            │
└───────────┼──────────────────────┼────────────────────────────────────────────┘
            │                      │
┌───────────┼──────────────────────┼────────────────────────────────────────────┐
│           │     Domain Layer      │                                            │
│  ┌────────▼───────────────────┐  │  ┌──────────────────────────────────────┐  │
│  │  WorkflowEngine             │  │  │  ProcessInstance (Event Sourced)      │  │
│  │  • advanceToken()           │  │  │  • variables: JSONB                  │  │
│  │  • executeAction()          │  │  │  • tokens: active/waiting/completed  │  │
│  │  • handleExclusive          │  │  │  • mergeVariables()                  │  │
│  │    Gateway() via Expr.Eval  │  │  └──────────────────────────────────────┘  │
│  └─────────────────────────────┘  │                                            │
│  ┌──────────────────────────────┐  │  ┌──────────────────────────────────────┐  │
│  │  ExpressionEvaluator         │  │  │  Task (AggregateRoot)                │  │
│  │  • Symfony ExpressionLang.   │  │  │  • candidateRoleId                   │  │
│  │  • Evaluates gateway conds   │  │  │  • candidateDepartmentId             │  │
│  └──────────────────────────────┘  │  │  • form_schema: JSONB                │  │
│  ┌──────────────────────────────┐  │  └──────────────────────────────────────┘  │
│  │  WorkflowTaskLink             │  │                                            │
│  │  • processInstanceId          │  │  ┌──────────────────────────────────────┐  │
│  │  • tokenId                    │  │  │  AssignmentStrategy (ValueObject)    │  │
│  │  • taskId                     │  │  │  • unassigned / specific_user /      │  │
│  │  • isCompleted                │  │  │    by_role / by_department /         │  │
│  └──────────────────────────────┘  │  │    process_initiator / from_variable  │  │
└────────────────────────────────────┴────────────────────────────────────────────┘
            │
┌───────────▼────────────────────────────────────────────────────────────────────┐
│                        Infrastructure Layer                                     │
│  PostgreSQL (ProcessInstance EventStore + Task JSONB) • Redis (cache) •        │
│  RabbitMQ (async events) • Mercure (real-time push)                            │
└────────────────────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Implementation |
|-----------|----------------|----------------|
| `WorkflowEngine` | Token lifecycle — advance, evaluate gateways, schedule timers | Domain service; pure PHP, no I/O |
| `ExpressionEvaluator` | Evaluate Symfony ExpressionLanguage conditions against process variables | Domain service wrapping Symfony EL |
| `ProcessInstance` | Event-sourced aggregate — holds tokens, variables, process state | AggregateRoot; reconstituted from EventStore |
| `WorkflowTaskLink` | Bridge entity: maps (processInstanceId, tokenId) → taskId | Workflow module domain entity; read by both modules |
| `Task` (TaskManager) | Holds form_schema JSONB, candidate pool columns, assignment state | TaskManager AggregateRoot; Doctrine XML mapping |
| `AssignmentResolver` | Resolves assignment strategy to assigneeId + candidates using Organization queries | Application service; uses OrganizationQueryPort |
| `OnTaskNodeActivated` | Listens to event, builds form_schema, dispatches CreateTaskCommand | Application event handler (event.bus) |
| `ExecuteTaskActionHandler` | Validates form data, merges variables, calls WorkflowEngine.executeAction | Application command handler (command.bus) |
| `FormFieldCollector` | Traverses ProcessGraph to collect fields for a given action key | Application service; used during validation |
| `GetTaskWorkflowContextHandler` | Returns form_schema + process context for task detail page | Application query handler (query.bus) |

---

## Component Boundaries

### What Talks to What

```
Frontend
  │
  ├── GET /tasks/{id}                    → TaskController → GetTaskHandler
  │                                         Returns: TaskDetailDTO (includes workflow_summary)
  │
  ├── GET /tasks/{id}/workflow-context   → TaskController → GetTaskWorkflowContextHandler
  │                                         Returns: form_schema + process_name + node_name
  │
  ├── POST /tasks/{id}/complete          → TaskController → ExecuteTaskActionCommand
  │     { action_key, form_data }           Handler: validates → merges → engine.executeAction
  │                                         Side effect: new TaskNodeActivatedEvent → new Task
  │
  ├── POST /tasks/{id}/claim             → TaskController → ClaimTaskCommand
  │                                         Validates candidate pool membership
  │
  └── POST /tasks/{id}/unclaim          → TaskController → UnclaimTaskCommand

Workflow Module
  │
  ├── WorkflowEngine (Domain)           → Pure logic only; no I/O
  │   • Called by: ExecuteTaskActionHandler
  │   • Emits events onto ProcessInstance (not dispatched directly)
  │
  ├── ProcessInstance (Domain)          → Event-sourced; state reconstructed from EventStore
  │   • TaskNodeActivatedEvent recorded here
  │   • Variables stored as JSONB (mergeVariables called by handler)
  │
  ├── OnTaskNodeActivated (App)         → Listens on event.bus (async)
  │   • Reads ProcessGraph to build form_schema
  │   • Dispatches CreateTaskCommand to TaskManager
  │   • Creates WorkflowTaskLink
  │
  └── ExecuteTaskActionHandler (App)    → Listens on command.bus (sync)
      • Reads WorkflowTaskLink to find process context
      • Calls FormFieldCollector (reads ProcessGraph)
      • Calls WorkflowEngine.executeAction
      • Saves ProcessInstance (triggers more events)

TaskManager Module
  │
  ├── Task entity                       → Stores form_schema (JSONB), candidateRoleId, candidateDepartmentId
  │
  ├── CreateTaskHandler                 → Accepts form_schema via command metadata
  │
  ├── ClaimTaskHandler                  → Validates candidate via OrganizationQueryPort
  │
  └── AssignmentResolver (App Service) → Called by OnTaskNodeActivated
      • Uses OrganizationQueryPort to find employees by role/department

Organization Module (read-only from TaskManager/Workflow)
  │
  └── OrganizationQueryPort             → Interface in TaskManager/Application/Port
      • Implementation: Infrastructure/Organization/DoctrineOrganizationQueryAdapter
      • Used by: AssignmentResolver, ClaimTaskHandler (candidate validation)
```

---

## Data Flow

### Primary Flow: Task Completion Advances Process

```
User clicks "Approve" in ActionFormDialog
    │
    ▼ POST /tasks/{id}/complete { action_key: "approve", form_data: { comment: "OK" } }
    │
    ▼ TaskController → dispatches ExecuteTaskActionCommand(taskId, actionKey, formData)
    │
    ▼ ExecuteTaskActionHandler (command.bus — sync, transactional)
    │   1. Finds WorkflowTaskLink by taskId → gets (processInstanceId, tokenId)
    │   2. Loads ProcessInstance (replays EventStore)
    │   3. Loads ProcessDefinitionVersion → builds ProcessGraph
    │   4. FormFieldCollector.collectForValidation(graph, nodeId, actionKey) → all fields
    │   5. Validates formData against required fields → throws FormValidationException if invalid
    │   6. ProcessInstance.mergeVariables(nodeId, actionKey, formData) → records event
    │   7. WorkflowEngine.executeAction(instance, tokenId, graph, actionKey)
    │       a. Finds outgoing transition for actionKey
    │       b. Moves token: source node → target node
    │       c. Calls advanceToken on target:
    │          - If task node → ProcessInstance.activateTaskNode() → records TaskNodeActivatedEvent
    │          - If XOR gateway → ExpressionEvaluator.evaluate(condition, variables) → picks path
    │          - If End → completes token, maybe completes process
    │   8. InstanceRepository.save(instance) → persists all recorded events to EventStore
    │   9. WorkflowTaskLink.markCompleted() → saved
    │
    ▼ DispatchDomainEventsMiddleware extracts events from UnitOfWork
    │
    ▼ EventBus dispatches TaskNodeActivatedEvent (async via RabbitMQ)
    │
    ▼ OnTaskNodeActivated handler (event.bus — async)
        1. Reads instance.variables (loads from EventStore)
        2. Reads ProcessGraph → finds node config + outgoing transitions
        3. Builds form_schema: { shared_fields: nodeConfig.formFields, actions: transitions.formFields }
        4. Resolves assignment (AssignmentResolver)
        5. Dispatches CreateTaskCommand → new Task created in TaskManager
        6. Creates new WorkflowTaskLink
```

### Secondary Flow: Loading Task Detail with Form Schema

```
User opens task detail page
    │
    ▼ GET /tasks/{id}/workflow-context
    │
    ▼ GetTaskWorkflowContextHandler (query.bus)
    │   1. Loads Task (to check workflowSummary)
    │   2. Finds WorkflowTaskLink by taskId
    │   3. Loads ProcessInstance → gets variables
    │   4. Loads ProcessDefinitionVersion → builds ProcessGraph
    │   5. Reads nodeConfig.formFields (shared_fields)
    │   6. Reads all outgoing transitions → collects each transition's formFields + actionKey + label
    │   7. Returns TaskWorkflowContextDTO {
    │        process_name, node_name, is_completed,
    │        form_schema: { shared_fields, actions: [{key, label, form_fields}] }
    │      }
    │
    ▼ Frontend: task.types.ts TaskWorkflowContextDTO
    │
    ▼ TaskDetailContent.vue renders:
        - Process badge (process_name + node_name)
        - Shared fields always visible
        - Action buttons: one per actions[]
        - Click action → ActionFormDialog shows action-specific fields
```

### Assignment Resolution Flow

```
OnTaskNodeActivated fires
    │
    ▼ AssignmentResolver.resolve(strategy, config, variables, organizationId)
    │
    ▼ Strategy dispatch:
    │   'specific_user'    → assigneeId = config.assignee_employee_id
    │   'process_initiator'→ assigneeId = variables['_task_creator_id']
    │   'from_variable'    → assigneeId = variables['_assignee_for_{nodeId}']
    │   'by_role'          → OrganizationQueryPort.findEmployeesByRole(roleId, orgId)
    │                         count==1 → direct assign
    │                         count>1  → candidateRoleId set, assigneeId=null (pool)
    │                         count==0 → assigneeId=null, log warning
    │   'by_department'    → OrganizationQueryPort.findEmployeesByDepartment(deptId, orgId)
    │                         same pool logic
    │   'unassigned'       → assigneeId=null, no candidates
    │
    ▼ Returns AssignmentResult { assigneeId, candidateRoleId, candidateDepartmentId }
    │
    ▼ Passed into CreateTaskCommand
```

### Claim Flow

```
User clicks "Assign to Me" on pool task
    │
    ▼ POST /tasks/{id}/claim
    │
    ▼ ClaimTaskHandler
    │   1. Loads Task → checks isPoolTask, existing assignee
    │   2. If already assigned → throws TaskClaimException (409)
    │   3. Validates current user is in candidate pool:
    │      - candidateRoleId? → check employee has that role (OrganizationQueryPort)
    │      - candidateDepartmentId? → check employee in department
    │   4. Task.claim(employeeId) → records TaskClaimedEvent
    │   5. TaskRepository.save(task)
    │
    ▼ Mercure publishes task update → other users see "Claimed by Alice"
```

---

## Suggested Build Order (Dependencies Between Components)

Dependencies flow strictly downward — each item requires the items above it to be complete.

### Layer 1 — Data Schema (no code dependencies)

```
1a. Migration: ProcessInstance.variables JSONB column
1b. Migration: Transition.form_fields JSONB column
1c. Migration: Task.candidate_role_id + candidate_department_id columns
1d. Migration: Task.form_schema JSONB column
```

All migrations are independent of each other and can be applied together.

### Layer 2 — Domain Entities (requires migrations)

```
2a. ProcessInstance: add variables(), mergeVariables() methods + variable-related events
2b. Task: add candidateRoleId, candidateDepartmentId, formSchema fields + getters
2c. AssignmentStrategy enum: complete all strategy values
2d. WorkflowTaskLink: ensure isCompleted flag persists correctly
```

`2a` and `2b` are independent. `2c` is used by `2b`. `2d` is standalone.

### Layer 3 — Application Services (requires Layer 2)

```
3a. OrganizationQueryPort interface + DoctrineOrganizationQueryAdapter
    → Required by: AssignmentResolver, ClaimTaskHandler
3b. AssignmentResolver service
    → Requires: 3a, 2c
3c. FormFieldCollector service (may already exist partially)
    → Requires: ProcessGraph API (already done)
3d. GetTaskWorkflowContextHandler query
    → Requires: 2a, 2b, ProcessGraph
```

`3a` must come before `3b`. Others are parallel once Layer 2 is done.

### Layer 4 — Command/Event Handlers (requires Layer 3)

```
4a. OnTaskNodeActivated: add form_schema building + AssignmentResolver call
    → Requires: 3b, 3c, 2b
4b. CreateTaskHandler: accept form_schema in command, persist to Task entity
    → Requires: 2b, 4a (command shape change)
4c. ExecuteTaskActionHandler: add formData validation + variable merge
    → Requires: 3c, 2a
4d. ClaimTaskHandler: implement + validate candidate pool via 3a
    → Requires: 3a, 2b
4e. UnclaimTaskHandler: implement
    → Requires: 2b
```

### Layer 5 — API Endpoints (requires Layer 4)

```
5a. GET /tasks/{id}/workflow-context endpoint
    → Requires: 3d
5b. POST /tasks/{id}/complete endpoint
    → Requires: 4c
5c. POST /tasks/{id}/claim endpoint
    → Requires: 4d
5d. POST /tasks/{id}/unclaim endpoint
    → Requires: 4e
5e. ListTasks: add "available" filter (pool tasks visible to candidates)
    → Requires: 2b
```

### Layer 6 — Frontend (requires Layer 5 APIs)

```
6a. ActionFormDialog.vue (already exists, may need polish)
    → Requires: 5a, 5b
6b. TaskDetailContent.vue: render workflow context + form schema
    → Requires: 5a
6c. TaskDetailPanel/TaskDetailFullPage routing
    → Requires: 6b
6d. Pool task UI: claim/unclaim buttons + candidate list
    → Requires: 5c, 5d
6e. TaskListPanel: "Available" tab filter
    → Requires: 5e
6f. "Start Process" button in TasksPage
    → Requires: existing StartProcess endpoint
6g. Process context badge on TaskCard
    → Requires: workflow_summary on TaskDTO (already in types)
6h. TaskNodeConfig.vue: Assignment strategy dropdown + conditional selectors
    → Requires: Org roles/departments APIs (already exist)
6i. Transition form fields in designer (TransitionPropertyPanel)
    → Requires: 1b migration + backend transition update API
```

---

## Architectural Patterns in Use

### Pattern 1: Form Schema per Action (not per Task)

**What:** Each outgoing transition from a task node defines its own `formFields` array. Shared fields live on the task node config. The backend assembles the full `form_schema` at task creation time and stores it as JSONB on the Task entity.

**When to use:** Whenever the UI needs to show different fields based on which button the user clicks. Approval flows where "Approve" and "Reject" require different data.

**Trade-offs:**
- Pro: Form schema travels with the task; no need to re-read process definition on every task open
- Pro: Works even if process definition is updated after tasks are created
- Con: form_schema duplicated on every task (acceptable — JSONB is cheap at task scale)

**Implementation in Procivo:**
```php
// OnTaskNodeActivated builds and stores this structure:
$formSchema = [
    'shared_fields' => $nodeConfig['formFields'] ?? [],
    'actions' => array_map(fn($t) => [
        'key'        => $t['action_key'] ?? 'complete',
        'label'      => $t['label'] ?? 'Complete',
        'form_fields' => $t['form_fields'] ?? [],
    ], $graph->outgoingTransitions($nodeId)),
];
// Stored in Task.metadata or Task.form_schema JSONB column
```

### Pattern 2: WorkflowTaskLink as Cross-Module Bridge

**What:** A dedicated entity in the Workflow module stores the (processInstanceId, tokenId, taskId) triple. Neither module directly references the other's entities — the link is the seam.

**When to use:** When two bounded contexts need loose coupling. The TaskManager module knows nothing about ProcessInstances; the Workflow module knows nothing about Task state machine.

**Trade-offs:**
- Pro: Modules can evolve independently; future microservice split is clean
- Con: Cross-module queries require joining through the link (acceptable)
- Con: Link integrity must be maintained manually (no FK across module boundaries)

**Key invariant:** One WorkflowTaskLink per (processInstanceId, tokenId) pair. When a token is reused (e.g., parallel re-entry), a new link is created.

### Pattern 3: Event-Sourced ProcessInstance with JSONB Variables

**What:** ProcessInstance state is reconstructed by replaying events from the EventStore. Process variables (form data submitted by users) are stored as a JSONB column alongside the event stream, updated on each `VariablesMergedEvent`.

**When to use:** Audit trail + replay is needed for process debugging. JSONB variables give flexibility for dynamic form schemas.

**Trade-offs:**
- Pro: Full audit trail of every step; variables have a clear write point
- Pro: XOR gateway can always read latest variables
- Con: Full replay on every read (snapshots needed at scale)

**Implementation note:** The `mergeVariables()` method on ProcessInstance records a domain event, which is then stored in the EventStore. Variables are NOT stored as a separate event sourcing stream — they are a denormalized JSONB column updated atomically.

### Pattern 4: OrganizationQueryPort — Anti-Corruption Layer

**What:** TaskManager defines an interface (`OrganizationQueryPort`) in its Application/Port directory. Infrastructure provides a Doctrine adapter (`DoctrineOrganizationQueryAdapter`) that queries the Organization module's tables.

**When to use:** When Module A needs data from Module B but must not couple to Module B's domain or repository interfaces.

**Trade-offs:**
- Pro: TaskManager stays decoupled from Organization module internals
- Pro: Port can be mocked in unit tests
- Con: Adds an abstraction layer; must keep port interface stable

```php
// In TaskManager/Application/Port/
interface OrganizationQueryPort {
    /** @return list<string> employee IDs */
    public function findEmployeeIdsByRole(string $roleId, string $orgId): array;
    public function findEmployeeIdsByDepartment(string $deptId, string $orgId): array;
    public function findManagerIdOfEmployee(string $employeeId, string $orgId): ?string;
}

// In TaskManager/Infrastructure/Organization/
class DoctrineOrganizationQueryAdapter implements OrganizationQueryPort {
    // Direct SQL/Doctrine queries on organization tables
}
```

### Pattern 5: Assignment Result as Value Object

**What:** `AssignmentResolver` returns an `AssignmentResult` value object containing `assigneeId`, `candidateRoleId`, `candidateDepartmentId`. This is passed directly into `CreateTaskCommand`.

**When to use:** When resolution logic is complex and the result must carry multiple related values without ambiguity.

```php
final readonly class AssignmentResult {
    public function __construct(
        public readonly ?string $assigneeId,
        public readonly ?string $candidateRoleId,
        public readonly ?string $candidateDepartmentId,
    ) {}

    public function isPoolTask(): bool {
        return null === $this->assigneeId
            && (null !== $this->candidateRoleId || null !== $this->candidateDepartmentId);
    }
}
```

---

## Integration Points

### Internal Module Boundaries

| Boundary | Communication Pattern | Direction | Notes |
|----------|-----------------------|-----------|-------|
| Workflow → TaskManager | Command dispatch via CommandBus | Workflow → TaskManager | OnTaskNodeActivated dispatches CreateTaskCommand |
| TaskManager → Workflow | Via ExecuteTaskActionCommand | TaskManager → Workflow | TaskController dispatches the command; handler reads WorkflowTaskLink |
| Workflow ↔ TaskManager | WorkflowTaskLink entity (Workflow module) | Bidirectional read | Both modules query this bridge entity |
| TaskManager → Organization | OrganizationQueryPort interface | TaskManager reads Organization | Doctrine adapter queries org tables directly |
| Workflow → EventStore | DoctrineEventStore | Workflow writes, reads | Event sourcing persistence |
| Any handler → Mercure | TaskMercurePublisher | Push after mutations | Real-time task updates to frontend |

### Key Data Ownership

| Data | Owner | Stored In | Accessed By |
|------|-------|-----------|-------------|
| Process variables | Workflow/ProcessInstance | EventStore + JSONB column | WorkflowEngine, ExpressionEvaluator |
| Form schema | TaskManager/Task | Task.form_schema JSONB | Frontend via GetTaskWorkflowContext |
| Assignment candidates | TaskManager/Task | Task columns | ClaimTaskHandler, ListTasksHandler |
| Token state | Workflow/ProcessInstance | EventStore | WorkflowEngine |
| Process ↔ Task mapping | Workflow/WorkflowTaskLink | workflow_task_links table | Both modules |

---

## Anti-Patterns to Avoid

### Anti-Pattern 1: Direct Repository Calls Across Module Boundaries

**What people do:** `TaskRepository` directly calls `EmployeeRepository` from the Organization module.

**Why it's wrong:** Couples bounded contexts; prevents future microservice split; Organization module's internals leak into TaskManager.

**Do this instead:** Use `OrganizationQueryPort` interface with a dedicated adapter. Port defines the contract in terms TaskManager cares about (employee IDs), not Organization internals.

### Anti-Pattern 2: Storing Form Schema Only in Process Definition

**What people do:** Task detail page loads the current process definition to get form fields, not what the task was created with.

**Why it's wrong:** If the process definition is updated while tasks are in flight, users see the wrong form fields. New version fields applied to old tasks, breaking validation.

**Do this instead:** Snapshot form_schema onto the Task entity at creation time (in OnTaskNodeActivated). Task is self-contained. Historical tasks always show the schema they were created with.

### Anti-Pattern 3: Synchronous Assignment Resolution in WorkflowEngine

**What people do:** Call `AssignmentResolver` from inside `WorkflowEngine` during token advance.

**Why it's wrong:** WorkflowEngine is a pure domain service with no I/O. Injecting I/O-bound queries violates Clean Architecture. It also makes the engine hard to test.

**Do this instead:** Resolution happens in `OnTaskNodeActivated` (Application layer). WorkflowEngine emits `TaskNodeActivatedEvent`; the event handler does the resolution. Engine stays pure.

### Anti-Pattern 4: Task Status and Workflow Completion Out of Sync

**What people do:** Task is marked "done" via the Symfony Workflow state machine, independent of workflow token completion.

**Why it's wrong:** Task status diverges from actual process state. ProcessInstance may still be running while the Task shows "done". This is documented as an existing bug in the codebase.

**Do this instead:** Task completion must flow through `ExecuteTaskActionCommand` which atomically: updates task, marks WorkflowTaskLink completed, and advances the workflow token. The Symfony Workflow `TransitionTask` command should only manage the status machine, not the cross-module completion.

### Anti-Pattern 5: Calling WorkflowEngine Directly from TaskController

**What people do:** TaskController injects WorkflowEngine and calls it directly.

**Why it's wrong:** Controller is in Presentation layer; WorkflowEngine is in Workflow/Domain layer. This violates module boundaries and bypasses command bus (losing transaction wrapping, middleware).

**Do this instead:** Controller dispatches `ExecuteTaskActionCommand` to the command bus. Handler does everything.

---

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 0-100 processes | Current architecture is fine. EventStore replay is fast for short processes. |
| 100-10k processes | Add ProcessInstance snapshot every 100 events. Cache form_schema computation. |
| 10k-100k active tasks | Add read models: denormalized task list table for ListTasks queries. Avoid joining EventStore on reads. |
| 100k+ | Separate Workflow and TaskManager into microservices. WorkflowTaskLink becomes a message contract. |

### Scaling Priorities

1. **First bottleneck:** ProcessInstance full event replay. Fix: snapshot at N events (every 100-500). Cache in Redis by instanceId.
2. **Second bottleneck:** Assignment resolution — N+1 org queries. Fix: batch query for all candidates; cache role membership.
3. **Third bottleneck:** ListTasks with complex RBAC filters. Fix: materialized task view with pre-computed visibility flags.

---

## Sources

- Direct codebase analysis: `/Users/leleka/Projects/procivo/backend/src/Workflow/` and `/Users/leleka/Projects/procivo/backend/src/TaskManager/`
- Project plan: `/Users/leleka/Projects/procivo/.planning/PROJECT.md`
- Existing architecture analysis: `/Users/leleka/Projects/procivo/.planning/codebase/ARCHITECTURE.md`
- Existing integration plan: `/Users/leleka/Projects/procivo/docs/WORKFLOW_TASKS_INTEGRATION_PLAN.md` (HIGH confidence — authored by project team)
- Assignment specification: `/Users/leleka/Projects/procivo/docs/TASK_ASSIGNMENT_SPEC.md` (HIGH confidence — references Camunda, Appian, BPMN 2.0 spec)
- Concerns analysis: `/Users/leleka/Projects/procivo/.planning/codebase/CONCERNS.md` (HIGH confidence — direct code analysis)
- BPM industry patterns: Camunda 8 worker model, Flowable task listener pattern, BPMN 2.0 humanPerformer/potentialOwner (MEDIUM confidence — training knowledge, verified against existing spec)

---

*Architecture research for: BPM Workflow-Task Integration (Procivo)*
*Researched: 2026-02-28*
