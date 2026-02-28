# Phase 2: Form Schema and Assignment - Research

**Researched:** 2026-02-28
**Domain:** Form schema snapshot embedding in Task entity, assignment strategy resolution via OrganizationQueryPort
**Confidence:** HIGH

## Summary

Phase 2 connects two existing but currently separate capabilities: (1) form schema construction that happens at query-time in `GetTaskWorkflowContextHandler` and (2) assignment resolution that already exists in `AssignmentResolver` + `CreateTaskHandler`. The core change is shifting form schema building from query-time to creation-time -- snapshotting the schema into the Task entity as JSONB when `OnTaskNodeActivated` fires -- so that the schema is immutable, decoupled from the process definition, and returned directly by the Task API without workflow module queries.

The codebase is well-prepared for this phase. `AssignmentResolver` already handles all 4 strategies (unassigned, specific_user, by_role, by_department) with auto-assignment for single-candidate pools. `OrganizationQueryPort` and its `DoctrineOrganizationQueryAdapter` implementation already bridge the TaskManager and Organization modules. `FormFieldCollector` already knows how to gather shared_fields from node config and action-specific fields from outgoing transitions. The `ProcessGraph` class provides all needed graph traversal methods.

The main implementation work is: (1) add a `formSchema` JSONB column to the Task entity + Doctrine mapping + migration, (2) build the form schema in `OnTaskNodeActivated` using the same logic currently in `GetTaskWorkflowContextHandler`, (3) store it in the Task at creation time, (4) expose it via `TaskDTO` and the existing GET API, (5) wire assignment resolution through `OnTaskNodeActivated` to `CreateTaskHandler`. Most of these are re-compositions of existing code rather than new logic.

**Primary recommendation:** Extract form schema building logic from `GetTaskWorkflowContextHandler` into `FormFieldCollector` (or a new `FormSchemaBuilder` service in the Workflow Application layer), call it from `OnTaskNodeActivated`, pass the built schema to `CreateTaskCommand`, and store it in the Task entity as JSONB. The assignment resolution path already works end-to-end -- verify it with tests.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| FORM-01 | OnTaskNodeActivated builds form_schema from TaskNode config (shared_fields) and outgoing transitions (action-specific fields) | `GetTaskWorkflowContextHandler` (lines 69-98) already contains exactly this logic. `FormFieldCollector` already collects shared + action fields. Need to move/reuse this logic in `OnTaskNodeActivated`. ProcessGraph is available via ProcessInstance's version snapshot. |
| FORM-02 | form_schema is snapshotted into Task metadata (JSONB) at creation time to prevent schema drift | Task entity needs a new `formSchema` field (nullable array, JSONB). Doctrine XML mapping needs a `json` type field. New migration adds the column. CreateTaskCommand needs a `formSchema` parameter. |
| FORM-03 | Each action in form_schema has its own set of fields plus shared fields from the task node | Already implemented in `GetTaskWorkflowContextHandler`: each action entry includes `form_fields` from its transition, and `shared_fields` are separate. `FormFieldCollector.injectAssigneeFieldsForDownstream` also auto-injects employee pickers for downstream `from_variable` nodes. |
| FORM-04 | API GET /api/v1/tasks/{id} returns form_schema alongside task data | TaskDTO needs a new `formSchema` field. `TaskDTO::fromEntity` reads it from Task entity. TaskController.show already serializes the full DTO to JSON. Currently form_schema comes from `workflow_context` -- after this phase it also lives directly on the task. |
| ASGN-01 | Assignment strategies: unassigned, specific_employee, by_role, by_department | `AssignmentStrategy` enum already has all 4 values: Unassigned, SpecificUser, ByRole, ByDepartment. `AssignmentResolver.resolve()` handles all 4 with match expression. |
| ASGN-02 | OnTaskNodeActivated resolves assignment strategy from node config and creates task with correct assignee/candidates | `OnTaskNodeActivated` already reads `assignment_strategy`, `assignee_employee_id`, `assignee_role_id`, `assignee_department_id` from taskConfig and passes them to CreateTaskCommand. `CreateTaskHandler` already calls `AssignmentResolver.resolve()`. |
| ASGN-03 | Pool tasks (by_role, by_department) -- task created with candidateRoleId/candidateDepartmentId, assigneeId = null | `AssignmentResolver.resolveByRole/resolveByDepartment` already return AssignmentResult with candidateRoleId/candidateDepartmentId when count > 1. Task entity already has these fields + Doctrine mapping + migration. |
| ASGN-04 | Auto-assign when single candidate in pool | `AssignmentResolver.resolveByRole` line 53: `if (1 === count($candidates))` returns assigneeId. Same for `resolveByDepartment` line 77. Already implemented. |
| ASGN-07 | OrganizationQueryPort anti-corruption layer -- TaskManager queries Organization module for role/department members | `OrganizationQueryPort` interface exists with 6 methods. `DoctrineOrganizationQueryAdapter` implements it using EmployeeRepository and EmployeeRoleRepository from Organization module. Already wired via Symfony DI. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| symfony/doctrine-bundle | 2.x (with Doctrine ORM 3.x) | Entity mapping, JSONB column type, migrations | Already used for all entities. JSONB via `type="json"` in XML mapping |
| doctrine/migrations-bundle | 3.x | Database schema migrations | Already used. New migration for form_schema column |
| symfony/messenger | 8.0.* | Command/query/event bus for CQRS | Already used. OnTaskNodeActivated is event.bus handler, CreateTaskCommand goes through command.bus |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHPUnit | ^13.0 | Unit testing | Test FormSchemaBuilder, AssignmentResolver, OnTaskNodeActivated integration |
| PHPStan | ^2.1 | Static analysis | Verify JSONB array types, nullable handling |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| JSONB column on Task entity | Separate form_schema table | Extra join, more complexity. JSONB is simpler -- schema is read-only after creation, no querying needed |
| Snapshotting at creation time | Query-time computation (current approach) | Current approach works but couples task detail to workflow module at read time. Snapshot decouples them and prevents schema drift when process definition is updated |
| Storing full schema in Task | Storing only schema reference | Reference means Task depends on process definition version at read time. Snapshot means Task is self-contained |

## Architecture Patterns

### Recommended Project Structure (additions to existing)
```
backend/src/
├── Workflow/
│   ├── Application/
│   │   └── Service/
│   │       ├── FormFieldCollector.php    # EXISTING - shared + action field collection
│   │       └── FormSchemaBuilder.php     # NEW - builds full form_schema structure
│   └── ...
├── TaskManager/
│   ├── Domain/
│   │   └── Entity/
│   │       └── Task.php                  # MODIFIED - add formSchema field
│   ├── Application/
│   │   ├── Command/
│   │   │   └── CreateTask/
│   │   │       ├── CreateTaskCommand.php # MODIFIED - add formSchema param
│   │   │       └── CreateTaskHandler.php # MODIFIED - pass formSchema to Task::create
│   │   └── DTO/
│   │       └── TaskDTO.php               # MODIFIED - add formSchema field
│   └── Infrastructure/
│       └── Persistence/
│           └── Doctrine/
│               └── Mapping/
│                   └── Task.orm.xml      # MODIFIED - add json field
└── ...
```

### Pattern 1: Form Schema Snapshot at Event-Handler Time
**What:** When `OnTaskNodeActivated` fires, build the complete form_schema from the process graph (node config + outgoing transitions) and embed it in the `CreateTaskCommand`, which stores it as JSONB in the Task entity.

**When to use:** Every time a workflow token reaches a Task node and creates a new task.

**Design:**
```php
// In OnTaskNodeActivated::__invoke()

// 1. Get ProcessGraph from version snapshot
$version = $this->versionRepository->findById($instance->versionId());
$graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());

// 2. Build form schema using FormSchemaBuilder
$formSchema = $this->formSchemaBuilder->build($graph, $event->nodeId);

// 3. Pass to CreateTaskCommand
$this->commandBus->dispatch(new CreateTaskCommand(
    // ... existing params ...
    formSchema: $formSchema,
));
```

**Source:** Logic derived from existing `GetTaskWorkflowContextHandler` lines 69-98.

### Pattern 2: FormSchemaBuilder Service (Extract from Handler)
**What:** A dedicated service that encapsulates form schema construction logic, reusable by both `OnTaskNodeActivated` (creation-time) and `GetTaskWorkflowContextHandler` (for backwards compatibility during transition).

**When to use:** Whenever a form schema needs to be built from a ProcessGraph and a node ID.

**Design:**
```php
final readonly class FormSchemaBuilder
{
    public function __construct(
        private FormFieldCollector $fieldCollector,
    ) {}

    /**
     * @return array{shared_fields: list<array<string, mixed>>, actions: list<array{key: string, label: string, form_fields: list<array<string, mixed>>}>}
     */
    public function build(ProcessGraph $graph, string $nodeId): array
    {
        $nodeConfig = $graph->nodeConfig($nodeId);
        $sharedFields = $nodeConfig['formFields'] ?? [];

        $outgoing = $graph->outgoingTransitions($nodeId);
        $actions = [];
        foreach ($outgoing as $transition) {
            $formFields = $transition['form_fields'] ?? [];
            $formFields = $this->fieldCollector->injectAssigneeFieldsForDownstream(
                $graph,
                (string) ($transition['target_node_id'] ?? ''),
                $formFields,
            );

            $actions[] = [
                'key' => $transition['action_key'] ?? 'complete',
                'label' => $transition['name'] ?? $transition['action_key'] ?? 'Complete',
                'form_fields' => $formFields,
            ];
        }

        return [
            'shared_fields' => $sharedFields,
            'actions' => $actions,
        ];
    }
}
```

**Why this pattern:**
- DRY: avoids duplicating schema building logic in OnTaskNodeActivated and GetTaskWorkflowContextHandler
- Single responsibility: FormSchemaBuilder only builds schemas, doesn't handle events or queries
- Testable: pure function with ProcessGraph input and array output

### Pattern 3: JSONB Column for Schemaless Snapshot
**What:** Store form_schema as a PostgreSQL JSONB column via Doctrine's `json` type. The column is nullable (null for tasks not created by workflow).

**When to use:** Any time structured but variably-shaped data needs to be stored on an entity.

**Doctrine XML mapping:**
```xml
<field name="formSchema" type="json" nullable="true" column="form_schema"/>
```

**Why:**
- form_schema structure varies per task node (different actions, different fields)
- No need to query individual form fields via SQL
- Matches the existing pattern in ProcessInstance.variables (also JSONB)
- Doctrine `json` type handles PHP array <-> JSONB conversion automatically

### Anti-Patterns to Avoid
- **Computing form_schema at read time only:** Current approach in `GetTaskWorkflowContextHandler`. Works but couples every task detail query to the workflow module. If the process definition is updated (new version published), the schema could change for existing active tasks -- violation of snapshot-at-creation principle.
- **Storing form_schema in WorkflowTaskLink instead of Task:** WorkflowTaskLink is a bridge entity in the Workflow module. Putting form_schema there still requires a cross-module query. The Task should be self-contained for its display data.
- **Making formSchema non-nullable:** Tasks created manually (not via workflow) won't have a form_schema. Making it required would break the existing create-task flow.
- **Re-implementing assignment resolution in OnTaskNodeActivated:** AssignmentResolver already handles everything. OnTaskNodeActivated should just pass the config through to CreateTaskCommand, which delegates to AssignmentResolver. Don't duplicate the match/resolve logic.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Form schema construction | New builder from scratch | Extract from existing `GetTaskWorkflowContextHandler` logic | The logic already exists and is tested implicitly through the API. Extract, don't rewrite |
| Assignment resolution | New resolver in OnTaskNodeActivated | Existing `AssignmentResolver` via `CreateTaskHandler` | Already handles all 4 strategies with auto-assign. Just pass config through |
| JSONB serialization | Custom serializer for form_schema | Doctrine `json` type | Handles PHP array to JSONB and back automatically. Already used for process variables |
| Cross-module organization queries | Direct repository access | Existing `OrganizationQueryPort` + `DoctrineOrganizationQueryAdapter` | ACL boundary already defined and implemented |

**Key insight:** This phase is primarily about *re-composing* existing code rather than building new logic. The form schema building and assignment resolution already work -- they just need to be invoked at creation time and stored.

## Common Pitfalls

### Pitfall 1: ProcessGraph Not Available in OnTaskNodeActivated
**What goes wrong:** `OnTaskNodeActivated` currently only receives `taskConfig` (the node's config) but not the process graph. To build form_schema, we need outgoing transitions which are on the graph, not in taskConfig.
**Why it happens:** `TaskNodeActivatedEvent` only carries `taskConfig` (node config), `processInstanceId`, `nodeId`, `tokenId`, `nodeName`.
**How to avoid:** In `OnTaskNodeActivated`, use the `processInstanceId` to load the `ProcessInstance`, get its `versionId`, load `ProcessDefinitionVersion`, and call `ProcessGraph::fromSnapshot()`. The handler already loads the ProcessInstance (line 33-36) -- just need to also load the version.
**Warning signs:** NullPointerException when trying to access graph in the handler.

### Pitfall 2: form_schema Null for Manually Created Tasks
**What goes wrong:** `Task::create()` is called both from workflow (OnTaskNodeActivated -> CreateTaskCommand) and from manual task creation (TaskController.create). Manual tasks don't have form_schema.
**Why it happens:** formSchema parameter is only provided by workflow path.
**How to avoid:** Make `formSchema` nullable on both `CreateTaskCommand` and `Task::create()`. Default to `null`. Frontend should handle `formSchema: null` gracefully (show no form fields, just standard task actions).
**Warning signs:** Error when creating task via POST /api/v1/organizations/{orgId}/tasks without formSchema.

### Pitfall 3: Schema Drift After Process Definition Update
**What goes wrong:** After snapshotting form_schema, a process designer updates the definition and publishes a new version. Existing active tasks still have the old schema -- which is correct behavior but could confuse users.
**Why it happens:** Snapshot-at-creation means the schema is immutable.
**How to avoid:** This is the intended behavior (FORM-02 explicitly says "snapshot to prevent schema drift"). Document it. In the future, if needed, a "refresh schema" admin action could be added.
**Warning signs:** User reports that changing a form field in the designer doesn't affect existing tasks. Expected behavior -- not a bug.

### Pitfall 4: Duplicate Data Between form_schema and workflow_context
**What goes wrong:** After this phase, `GET /tasks/{id}` returns form_schema both on the task directly AND inside `workflow_context.form_schema` (from GetTaskWorkflowContextHandler). Double data, confusion about which is authoritative.
**Why it happens:** `GetTaskWorkflowContextHandler` still computes form_schema dynamically.
**How to avoid:** After this phase, `GetTaskWorkflowContextHandler` should prefer the Task's snapshotted form_schema when available, falling back to dynamic computation only for tasks created before the migration. Alternatively, keep both during transition and clean up in a later phase. The task's `form_schema` is authoritative.
**Warning signs:** Frontend consuming different schema from two response fields.

### Pitfall 5: Migration for Existing Tasks
**What goes wrong:** Existing tasks in the database don't have `form_schema` column. Migration adds nullable column, but existing workflow tasks have no schema.
**Why it happens:** Backfilling would require reconstructing schema from historical process definition versions.
**How to avoid:** Make the column nullable. Existing tasks get `null`. Frontend falls back to `workflow_context.form_schema` when task's `form_schema` is null. New tasks get the snapshot. No backfill needed.
**Warning signs:** Null form_schema for old tasks -- expected, not an error.

### Pitfall 6: Assignment Resolution Timing
**What goes wrong:** `AssignmentResolver` queries `OrganizationQueryPort` for role/department members. If the org structure changes between process start and task node activation, the resolution uses current (not historical) org data.
**Why it happens:** Assignment resolution is always against current organization state -- there's no org snapshot.
**How to avoid:** This is standard BPM behavior (Camunda, Bonita, etc. all resolve against current identity data). Document as intentional. If an employee is added to a role after a pool task is created, they can see it in "Available" -- which is correct.
**Warning signs:** None -- this is the expected behavior.

## Code Examples

Verified patterns from codebase inspection:

### Current: Form Schema Construction in GetTaskWorkflowContextHandler
```php
// Source: backend/src/Workflow/Application/Query/GetTaskWorkflowContext/GetTaskWorkflowContextHandler.php:69-98
$outgoing = $graph->outgoingTransitions($nodeId);
$actions = [];
foreach ($outgoing as $transition) {
    $formFields = $transition['form_fields'] ?? [];
    $formFields = $this->fieldCollector->injectAssigneeFieldsForDownstream(
        $graph,
        (string) ($transition['target_node_id'] ?? ''),
        $formFields,
    );

    $actions[] = [
        'key' => $transition['action_key'] ?? 'complete',
        'label' => $transition['name'] ?? $transition['action_key'] ?? 'Complete',
        'form_fields' => $formFields,
    ];
}

$sharedFields = $nodeConfig['formFields'] ?? [];

return new TaskWorkflowContextDTO(
    // ...
    formSchema: [
        'shared_fields' => $sharedFields,
        'actions' => $actions,
    ],
);
```

### Current: OnTaskNodeActivated -- Assignment Config Extraction
```php
// Source: backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php:52-63
$assignmentStrategy = isset($taskConfig['assignment_strategy'])
    ? (string) $taskConfig['assignment_strategy']
    : 'unassigned';
$assigneeEmployeeId = isset($taskConfig['assignee_employee_id'])
    ? (string) $taskConfig['assignee_employee_id']
    : null;
$assigneeRoleId = isset($taskConfig['assignee_role_id'])
    ? (string) $taskConfig['assignee_role_id']
    : null;
$assigneeDepartmentId = isset($taskConfig['assignee_department_id'])
    ? (string) $taskConfig['assignee_department_id']
    : null;
```

### Current: AssignmentResolver -- Auto-Assign Logic
```php
// Source: backend/src/TaskManager/Application/Service/AssignmentResolver.php:45-59
private function resolveByRole(?string $roleId, string $organizationId): AssignmentResult
{
    if (null === $roleId) {
        return new AssignmentResult(AssignmentStrategy::ByRole, null, null, null);
    }

    $candidates = $this->organizationQueryPort->findActiveEmployeeIdsByRoleId($roleId, $organizationId);

    if (1 === \count($candidates)) {
        return new AssignmentResult(
            AssignmentStrategy::ByRole,
            $candidates[0]['employeeId'],
            null, // no candidateRoleId -- auto-assigned
            null,
        );
    }

    return new AssignmentResult(
        AssignmentStrategy::ByRole,
        null,
        $roleId,
        null,
    );
}
```

### Proposed: Task Entity -- formSchema Field
```php
// Task.php -- new nullable JSONB field
/** @var array<string, mixed>|null */
private ?array $formSchema = null;

public static function create(
    // ... existing params ...
    ?array $formSchema = null,
): self {
    $task = new self();
    // ... existing assignments ...
    $task->formSchema = $formSchema;
    // ...
    return $task;
}

/** @return array<string, mixed>|null */
public function formSchema(): ?array
{
    return $this->formSchema;
}
```

### Proposed: Doctrine XML Mapping Addition
```xml
<!-- In Task.orm.xml, add after candidateDepartmentId field -->
<field name="formSchema" type="json" nullable="true" column="form_schema"/>
```

### Proposed: Migration
```php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE task_manager_tasks ADD COLUMN form_schema JSONB DEFAULT NULL');
}
```

### Proposed: OnTaskNodeActivated -- Build and Pass Schema
```php
// In OnTaskNodeActivated::__invoke(), after loading instance:
$version = $this->versionRepository->findById($instance->versionId());
$graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());

$formSchema = $this->formSchemaBuilder->build($graph, $event->nodeId);

// In CreateTaskCommand dispatch:
$this->commandBus->dispatch(new CreateTaskCommand(
    // ... existing params ...
    formSchema: $formSchema,
));
```

### Proposed: TaskDTO -- formSchema Exposure
```php
// TaskDTO -- add formSchema
public function __construct(
    // ... existing params ...
    /** @var array<string, mixed>|null */
    public ?array $formSchema = null,
) {}

public static function fromEntity(Task $task, array $availableTransitions = []): self
{
    return new self(
        // ... existing fields ...
        formSchema: $task->formSchema(),
    );
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Form schema computed at query time (GetTaskWorkflowContextHandler) | Snapshot at creation time (JSONB in Task entity) | This phase | Decouples task detail from workflow module, prevents schema drift |
| Assignment config stored but not resolved at creation | Fully resolved via AssignmentResolver at creation | Already done (Phase 3 prep) | Tasks are created with correct assignee/candidates from the start |
| Task entity has no workflow-specific data | Task entity stores formSchema as JSONB | This phase | Self-contained task entities for API responses |

## Open Questions

1. **Should GetTaskWorkflowContextHandler still compute form_schema after this phase?**
   - What we know: After snapshotting, the Task has its own form_schema. GetTaskWorkflowContextHandler currently computes it dynamically.
   - What's unclear: Should we keep both (redundancy) or migrate GetTaskWorkflowContextHandler to read from Task?
   - Recommendation: Keep both temporarily. GetTaskWorkflowContextHandler serves as fallback for pre-migration tasks (form_schema IS NULL). After all old tasks are completed/archived, the dynamic computation can be removed. This is a future cleanup, not Phase 2 scope.

2. **OnTaskNodeActivated needs ProcessDefinitionVersionRepository -- new dependency**
   - What we know: The handler currently doesn't load the version/graph. It only uses taskConfig from the event.
   - What's unclear: Adding ProcessDefinitionVersionRepository as a dependency is straightforward, but it adds coupling.
   - Recommendation: Inject `ProcessDefinitionVersionRepositoryInterface` into OnTaskNodeActivated constructor. This is acceptable -- the handler already depends on ProcessInstanceRepositoryInterface. The version lookup is needed to build the form schema from the graph.

3. **Should form_schema include auto-injected assignee fields (from_variable)?**
   - What we know: `FormFieldCollector.injectAssigneeFieldsForDownstream` adds employee picker fields for downstream nodes with `from_variable` assignment strategy. This is part of the transition's effective form fields.
   - What's unclear: Should these auto-injected fields be part of the snapshot?
   - Recommendation: YES. The snapshot should include all effective fields, including auto-injected ones. This is what the user sees and fills out. If the downstream node's assignment strategy changes after snapshot, the old task's form still has the field -- acceptable because the snapshot represents the state at task creation time.

4. **AssignmentResolver auto-assign: should candidateRoleId be null when auto-assigned?**
   - What we know: Current code returns `candidateRoleId: null` when count == 1 (auto-assigned). This means the task looks like a directly-assigned task, not a pool task that was auto-assigned.
   - What's unclear: Should we preserve candidateRoleId even on auto-assign for audit/display purposes?
   - Recommendation: This is a design choice. Current behavior (null candidateRoleId on auto-assign) is simpler and correct -- the task IS directly assigned, just automatically. If audit trail is needed later, the event log has the full history. Keep current behavior for now.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 13.0 |
| Config file | backend/phpunit.dist.xml |
| Quick run command | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow tests/Unit/TaskManager --testdox` |
| Full suite command | `cd backend && ./vendor/bin/phpunit` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| FORM-01 | FormSchemaBuilder builds schema from node config + transitions | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow/Application/Service/FormSchemaBuilderTest.php -x` | No -- Wave 0 |
| FORM-02 | OnTaskNodeActivated passes formSchema to CreateTaskCommand | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow/Application/EventHandler/OnTaskNodeActivatedTest.php -x` | No -- Wave 0 |
| FORM-03 | Each action has own fields plus shared fields | unit | Same as FORM-01 (FormSchemaBuilderTest) | No -- Wave 0 |
| FORM-04 | TaskDTO includes formSchema from Task entity | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/TaskManager/Application/DTO/TaskDTOTest.php -x` | No -- Wave 0 |
| ASGN-01 | AssignmentResolver handles all 4 strategies | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/TaskManager/Application/Service/AssignmentResolverTest.php -x` | No -- Wave 0 |
| ASGN-02 | CreateTaskHandler uses AssignmentResolver result | unit | `cd backend && ./vendor/bin/phpunit tests/Unit/TaskManager/Application/Command/CreateTaskHandlerTest.php -x` | No -- Wave 0 |
| ASGN-03 | Pool tasks get candidateRoleId/candidateDepartmentId | unit | Same as ASGN-01 (AssignmentResolverTest) | No -- Wave 0 |
| ASGN-04 | Auto-assign when single candidate | unit | Same as ASGN-01 (AssignmentResolverTest) | No -- Wave 0 |
| ASGN-07 | OrganizationQueryPort provides role/dept member queries | unit | Tested implicitly via AssignmentResolverTest (mock port) | No -- Wave 0 |

### Sampling Rate
- **Per task commit:** `cd backend && ./vendor/bin/phpunit tests/Unit/Workflow tests/Unit/TaskManager --testdox`
- **Per wave merge:** `cd backend && ./vendor/bin/phpunit`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/Workflow/Application/Service/FormSchemaBuilderTest.php` -- covers FORM-01, FORM-03
- [ ] `tests/Unit/Workflow/Application/EventHandler/OnTaskNodeActivatedTest.php` -- covers FORM-02
- [ ] `tests/Unit/TaskManager/Application/Service/AssignmentResolverTest.php` -- covers ASGN-01, ASGN-03, ASGN-04
- [ ] `tests/Unit/TaskManager/Application/Command/CreateTaskHandlerTest.php` -- covers ASGN-02

*(TaskDTOTest for FORM-04 is trivial and can be covered in the plan that adds the field, not Wave 0)*

## Sources

### Primary (HIGH confidence)
- Codebase inspection: OnTaskNodeActivated.php, GetTaskWorkflowContextHandler.php, FormFieldCollector.php, AssignmentResolver.php, CreateTaskHandler.php, CreateTaskCommand.php, Task.php, TaskDTO.php, TaskController.php, ProcessGraph.php, ProcessInstance.php, WorkflowEngine.php, OrganizationQueryPort.php, DoctrineOrganizationQueryAdapter.php, Task.orm.xml, AssignmentStrategy.php, AssignmentResult.php, DoctrineTaskRepository.php, TaskRepositoryInterface.php, TaskWorkflowContextDTO.php, WorkflowTaskLink.php, ProcessDefinitionVersion.php -- all read directly from source
- Phase 1 research (01-RESEARCH.md) -- established patterns for FormSchemaValidator, variable namespacing, ExpressionEvaluator
- TASK_ASSIGNMENT_SPEC.md -- comprehensive assignment strategy design with resolution logic and auto-assign rules
- Existing migrations (Version20260227100000.php) -- confirmed assignment_strategy, candidate_role_id, candidate_department_id already added to DB

### Secondary (MEDIUM confidence)
- Frontend types (process-definition.types.ts) -- confirmed FormFieldDefinition shape, TransitionDTO with form_fields and action_key
- Doctrine ORM documentation on JSON type -- standard approach for JSONB columns in PostgreSQL

### Tertiary (LOW confidence)
- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all libraries already installed, no new dependencies needed
- Architecture: HIGH -- patterns derived from reading actual codebase. Form schema building logic already exists, just needs extraction and repositioning
- Pitfalls: HIGH -- identified through code path analysis. ProcessGraph dependency is the main integration challenge
- Assignment: HIGH -- entire assignment pipeline already implemented. Phase 2 is verification + testing, not new implementation

**Research date:** 2026-02-28
**Valid until:** 2026-03-28 (stable domain, internal codebase patterns)
