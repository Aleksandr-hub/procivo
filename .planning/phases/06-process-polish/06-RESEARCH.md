# Phase 6: Process Polish — Research

**Researched:** 2026-03-01
**Domain:** BPM tech debt elimination — formSchema snapshots, AssignmentStrategy enum, process cancellation UI, paginated search, UI layout polish
**Confidence:** HIGH — all findings based on direct codebase inspection

---

## Summary

Phase 6 eliminates v1.0 tech debt across two subsystems (TaskManager + Workflow) and adds two missing features (cancel from detail page, search/pagination). The work is well-scoped: the codebase already has the infrastructure in place for most requirements; the gaps are precisely identified.

**PLSH-01 + PLSH-02 (formSchema):** The core tech debt. `GetTaskWorkflowContextHandler` contains a full duplicate of the logic inside `FormSchemaBuilder.build()` — it manually iterates transitions, injects assignee fields, and builds the same `{shared_fields, actions}` shape. When a task's `GET /api/v1/organizations/{org}/tasks/{id}` is called, it invokes `GetTaskWorkflowContextQuery`, which reads from the live process graph — not from `Task.formSchema`. This violates snapshot isolation: if the definition changes, the form schema silently diverges. The fix is: (a) refactor `GetTaskWorkflowContextHandler` to use `FormSchemaBuilder`; and (b) serve `form_schema` from `Task.formSchema` in the task detail query, avoiding the Workflow module for read-only display.

**PLSH-03 (from_variable enum):** `AssignmentStrategy` PHP enum is missing `FromVariable`. The `OnTaskNodeActivated` handler already handles `from_variable` as a raw string comparison and translates it to `specific_user` before calling `CreateTaskCommand`. The `AssignmentResolver` fails with `ValueError` on `AssignmentStrategy::from('from_variable')` because the enum case does not exist. The fix is a one-case enum addition, plus a guard in `AssignmentResolver` to short-circuit before `::from()` is called (or `AssignmentResolver` learns a `FromVariable` branch). The frontend designer already exposes the `from_variable` strategy option in `TaskNodeConfig.vue`.

**PLSH-04 (cancel from detail page):** `POST /api/v1/organizations/{org}/process-instances/{id}/cancel` already exists and works. `ProcessInstanceDetailPage.vue` already has a Cancel button and `cancelInstance()` handler. The cancel button is shown when `status === 'running'`. The backend `CancelProcessHandler` is already implemented. This requirement is largely already done — only verification and possibly a confirmation dialog are needed.

**PLSH-05 (search/pagination):** `ListProcessInstancesHandler` already filters by `status`. It is missing: (a) name/definition search, and (b) pagination. `ProcessInstancesPage.vue` already has a status filter `Select` and calls `onFilterChange`. What is missing: a search input wired to the query, and true pagination from the backend (the DataTable has `:rows="20"` client-side only — the backend returns ALL rows).

**PLSH-06 (UI polish):** `TaskDetailContent.vue` already has a structured two-column layout with a sticky header, sidebar, and multiple sections. The `ProcessContextCard.vue` uses a flex layout. The ProgressBar in ProcessContextCard uses `completedStepCount * 10` as percentage, which is wrong (10 steps = 100%, hardcoded). The polish work is CSS/layout adjustments in existing components — no new components needed.

**Primary recommendation:** Address requirements in this order: PLSH-02 (dedup FormSchemaBuilder) → PLSH-01 (serve snapshot from Task entity) → PLSH-03 (add enum case) → PLSH-05 (add search + real pagination) → PLSH-04 (verify cancel + add confirm dialog) → PLSH-06 (layout polish).

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PLSH-01 | Frontend reads Task.formSchema snapshot instead of live schema from workflow context | Task entity already stores `formSchema` as JSON; `TaskDTO` already serializes it; `TaskController::show()` fetches live via `GetTaskWorkflowContextQuery`. Fix: serve snapshot from `TaskDTO.formSchema` instead of re-computing from graph |
| PLSH-02 | Single FormSchemaBuilder used by both task creation and task query (dedup) | `FormSchemaBuilder.build()` exists in `Workflow/Application/Service/`. `GetTaskWorkflowContextHandler` duplicates its logic manually. Fix: inject `FormSchemaBuilder` into the handler and delegate |
| PLSH-03 | FromVariable case added to AssignmentStrategy backend enum + employee picker on start form | PHP `AssignmentStrategy` enum has 4 cases; `from_variable` missing. `AssignmentResolver::resolve()` calls `AssignmentStrategy::from($strategy)` which will throw for `from_variable`. `DynamicFormField.vue` already renders `employee` type picker |
| PLSH-04 | User can cancel a running process instance from ProcessInstanceDetailPage | Backend `CancelProcessHandler` + route `/cancel` exist. `ProcessInstanceDetailPage.vue` already has cancel button + handler. Needs: confirmation dialog before dispatching cancel |
| PLSH-05 | User can filter process instance list by status, search by name, paginate results | `ListProcessInstancesHandler` already accepts `status`. Missing: `name` search param, `page`/`limit` params. Frontend `ProcessInstancesPage.vue` has DataTable with client-side paginator only |
| PLSH-06 | Task detail page UI aligned with design intent (spacing, card structure, field alignment) | `TaskDetailContent.vue` and `ProcessContextCard.vue` exist. ProgressBar percentage is broken (hardcoded multiplier). Alignment, spacing, card structure adjustments in existing CSS |

</phase_requirements>

---

## Standard Stack

### Core (already in use — no new dependencies needed)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Symfony Messenger (command.bus / query.bus) | 7.x | CQRS buses | Project standard for all commands and queries |
| Doctrine DBAL | 3.x | Read-model queries (ListProcessInstancesHandler) | Direct SQL for read models, avoids ORM overhead |
| Doctrine ORM + XML mappings | 3.x | Task entity persistence | Project standard (no annotations) |
| PrimeVue 4 | 4.x | DataTable, Select, InputText, ProgressBar, Dialog | Project UI library — all form widgets |
| Pinia 3 | 3.x | process-instance.store, task.store | Project state management |
| Vue 3.5 + TypeScript 7 | — | Frontend components | Project standard |
| vue-i18n | — | uk.json / en.json | All UI strings through i18n |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PrimeVue `ConfirmDialog` + `useConfirm` | 4.x | Cancel confirmation dialog | Already used in `TaskDetailContent.vue` — same pattern for cancel |
| PrimeVue `DataTable` with server-side pagination | 4.x | Paginated process instance list | Use `lazy` + `@page` event to fetch pages from backend |
| PrimeVue `InputText` with search | 4.x | Name search input | Simple controlled input with debounce |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Real server-side pagination | Client-side `:rows="20"` | Client-side is already there; server-side required by PLSH-05 for correctness at scale |
| Extend `GetTaskWorkflowContextHandler` | New dedicated `GetTaskFormSchemaQuery` | Extending existing handler with `FormSchemaBuilder` injection is simpler and sufficient |
| Separate `from_variable` branch in `AssignmentResolver` | Keeping OnTaskNodeActivated raw-string handling | Enum case is the correct type-safe fix; `AssignmentResolver` should guard before `::from()` |

---

## Architecture Patterns

### Recommended Project Structure

All changes are modifications to existing files. No new modules or directories.

```
backend/src/
├── TaskManager/Domain/ValueObject/AssignmentStrategy.php   ← add FromVariable case
├── TaskManager/Application/Service/AssignmentResolver.php  ← add FromVariable guard
├── Workflow/Application/Query/GetTaskWorkflowContext/
│   └── GetTaskWorkflowContextHandler.php                  ← inject FormSchemaBuilder, use snapshot
├── Workflow/Application/Query/ListProcessInstances/
│   └── ListProcessInstancesQuery.php                      ← add search, page, limit
│   └── ListProcessInstancesHandler.php                    ← add WHERE ILIKE, LIMIT/OFFSET
frontend/src/
├── modules/workflow/pages/ProcessInstancesPage.vue         ← add search input + lazy pagination
├── modules/workflow/pages/ProcessInstanceDetailPage.vue    ← add cancel confirmation
├── modules/workflow/stores/process-instance.store.ts       ← update fetchInstances signature
├── modules/tasks/components/TaskDetailContent.vue          ← PLSH-01 + PLSH-06 layout adjustments
├── modules/tasks/components/ProcessContextCard.vue         ← fix ProgressBar percentage
```

### Pattern 1: FormSchemaBuilder Injection into GetTaskWorkflowContextHandler

**What:** Replace manual transition iteration + assignee field injection with a single `FormSchemaBuilder::build()` call.

**When to use:** Any place that needs to compute form schema for a task node.

**Example (current duplicated code to replace):**
```php
// BEFORE — GetTaskWorkflowContextHandler (lines 69-83): manually iterates transitions
$outgoing = $graph->outgoingTransitions($nodeId);
$actions = [];
foreach ($outgoing as $transition) {
    $formFields = $transition['form_fields'] ?? [];
    $formFields = $this->fieldCollector->injectAssigneeFieldsForDownstream(...);
    $actions[] = [...];
}
$sharedFields = $nodeConfig['formFields'] ?? [];
$formSchema = ['shared_fields' => $sharedFields, 'actions' => $actions];

// AFTER — one call:
$formSchema = $this->formSchemaBuilder->build($graph, $nodeId);
```

**Required change:** Replace `FormFieldCollector $fieldCollector` dependency with `FormSchemaBuilder $formSchemaBuilder` in `GetTaskWorkflowContextHandler`.

### Pattern 2: Serve formSchema Snapshot from Task.formSchema (PLSH-01)

**What:** When `TaskController::show()` builds the response, prefer `Task.formSchema` (already snapshotted at creation) over the live context from `GetTaskWorkflowContextHandler`.

**Current flow:**
```
GET /tasks/{id}
  → GetTaskQuery (returns TaskDTO with formSchema field)
  → GetTaskWorkflowContextQuery (reads live graph, recomputes form_schema)
  → merges: taskData['workflow_context'] = $workflowContext (overrides with live data)
```

**Correct flow for PLSH-01:**
```
GET /tasks/{id}
  → GetTaskQuery (returns TaskDTO with formSchema field from Task entity snapshot)
  → GetTaskWorkflowContextQuery (returns context WITHOUT recomputing form_schema from live graph)
  → taskData['workflow_context']['form_schema'] = TaskDTO.formSchema (use snapshot)
```

**Important nuance:** `GetTaskWorkflowContextHandler` must still return the completed task's context (processInstanceId, processName, nodeName, etc.). Only `form_schema` should come from snapshot. Two clean options:
- Option A: `GetTaskWorkflowContextHandler` returns context metadata only (no form_schema), and `TaskController::show()` merges formSchema from `TaskDTO.formSchema`.
- Option B: `GetTaskWorkflowContextHandler` reads `Task.formSchema` from the task entity (cross-module query — less clean).

**Recommendation:** Option A — `TaskController::show()` merges the snapshot. The handler strips `form_schema` from its return or returns null for it; the controller injects from TaskDTO.

### Pattern 3: AssignmentStrategy Enum Extension (PLSH-03)

**What:** Add `FromVariable` case to the PHP enum. Guard `AssignmentResolver` so it doesn't call `::from()` for `from_variable` (since it's resolved at task-node-activated time, not at task creation time).

**Current state:**
```php
// AssignmentStrategy.php — MISSING:
case FromVariable = 'from_variable';

// AssignmentResolver.php — WILL THROW:
$assignmentStrategy = AssignmentStrategy::from($strategy);  // ValueError for 'from_variable'
return match ($assignmentStrategy) {
    AssignmentStrategy::Unassigned => ...,
    AssignmentStrategy::SpecificUser => ...,
    AssignmentStrategy::ByRole => ...,
    AssignmentStrategy::ByDepartment => ...,
    // No FromVariable case!
};
```

**Fix:**
```php
// AssignmentStrategy.php:
case FromVariable = 'from_variable';

// AssignmentResolver.php:
// from_variable is pre-resolved in OnTaskNodeActivated before CreateTaskCommand is called.
// If it arrives here, treat as unassigned (defensive guard).
AssignmentStrategy::FromVariable => new AssignmentResult(
    AssignmentStrategy::Unassigned, null, null, null,
),
```

**Note:** `OnTaskNodeActivated` already handles `from_variable` by translating to `specific_user` + resolved employee ID before dispatching `CreateTaskCommand`. So `AssignmentResolver` should never see `from_variable` in practice — but the enum must exist for `::from()` to not throw on any code path that touches the strategy string.

### Pattern 4: ListProcessInstances — Search and Pagination (PLSH-05)

**What:** Add `search` (definition name ILIKE), `page`, and `limit` to `ListProcessInstancesQuery` and handler.

**Current state:**
```php
// ListProcessInstancesQuery — only has organizationId + status
// ListProcessInstancesHandler — no LIMIT/OFFSET, no name filter
```

**Fix (Query):**
```php
final readonly class ListProcessInstancesQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $search = null,    // add
        public int $page = 1,             // add
        public int $limit = 20,           // add
    ) {}
}
```

**Fix (Handler — DBAL pattern already used):**
```php
if (null !== $query->search) {
    $qb->andWhere('definition_name ILIKE :search')
       ->setParameter('search', '%' . $query->search . '%');
}
$offset = ($query->page - 1) * $query->limit;
$qb->setMaxResults($query->limit)->setFirstResult($offset);

// Count query for total (needed for DataTable pagination):
$total = $this->connection->fetchOne('SELECT COUNT(*) FROM workflow_process_instances_view WHERE ...');
// Return: ['items' => [...], 'total' => $total, 'page' => $page, 'limit' => $limit]
```

**Frontend (DataTable lazy pagination):**

PrimeVue DataTable supports `lazy` mode. The `@page` event provides `{ page, rows }`.

```vue
<DataTable
  :value="instanceStore.instances"
  lazy
  :totalRecords="instanceStore.total"
  :rows="20"
  @page="onPage"
  paginator
>
```

```typescript
async function onPage(event: DataTablePageEvent) {
  await instanceStore.fetchInstances(orgId.value, {
    status: filterStatus.value,
    search: searchQuery.value,
    page: event.page + 1,  // PrimeVue is 0-indexed
    limit: event.rows,
  })
}
```

**Response shape change:** The backend list endpoint currently returns `list<ProcessInstanceDTO>`. Must change to `{ items: list<ProcessInstanceDTO>, total: int, page: int, limit: int }` or add `X-Total-Count` header. Inline JSON body change is simpler.

### Pattern 5: Cancel Confirmation Dialog (PLSH-04)

**What:** `ProcessInstanceDetailPage.vue` has a Cancel button but calls `cancelInstance()` directly without confirmation. Add a confirmation step using PrimeVue `useConfirm`.

**Current state:** Button exists, works. No confirmation. `useConfirm` + `ConfirmDialog` are already used in `TaskDetailContent.vue` — same pattern applies.

```typescript
// In ProcessInstanceDetailPage.vue
const confirm = useConfirm()

function confirmCancel() {
  confirm.require({
    message: t('workflow.confirmCancelInstance'),
    header: t('common.confirm'),
    acceptLabel: t('workflow.cancel'),
    rejectLabel: t('common.back'),
    accept: () => cancelInstance(),
  })
}
```

### Pattern 6: ProgressBar Percentage Fix (PLSH-06)

**Current bug in `ProcessContextCard.vue`:**
```vue
<ProgressBar :value="completedStepCount * 10" :show-value="false" />
```
This hardcodes 10 steps = 100%. Breaks for any process with a different number of steps.

**Fix:** Pass total step count as a prop and compute the correct percentage:
```typescript
// In TaskDetailContent.vue:
const totalSteps = computed(() => stepperSteps.value.length || 1)

// In ProcessContextCard.vue:
defineProps<{
  completedStepCount: number
  totalStepCount: number     // add
}>()
// Template:
:value="Math.round((completedStepCount / totalStepCount) * 100)"
```

### Anti-Patterns to Avoid

- **Re-implementing FormSchemaBuilder logic**: `GetTaskWorkflowContextHandler` should call `FormSchemaBuilder::build()`, not duplicate its loop.
- **Serving live graph data as form_schema on task detail**: Task actions change when the workflow definition changes. The snapshot in `Task.formSchema` is the authoritative source for what the user was shown.
- **Client-side-only pagination**: `DataTable :rows="20"` with all data from backend is fine for dozens of rows but breaks for hundreds. The backend must provide real LIMIT/OFFSET.
- **Adding `from_variable` to `AssignmentResolver` match arms with real resolution logic**: Resolution of `from_variable` to a specific employee already happens in `OnTaskNodeActivated`. Do not duplicate it in `AssignmentResolver`.
- **Cross-module entity reads**: `GetTaskWorkflowContextHandler` (in Workflow module) should not directly query `task_manager_tasks` table. If it needs the form schema, the controller should merge it from the TaskDTO.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Confirmation dialog | Custom modal component | PrimeVue `useConfirm` + `ConfirmDialog` | Already registered globally; used in `TaskDetailContent.vue` |
| Debounced search input | Manual `setTimeout` cleanup | VueUse `useDebounceFn` or simple `watch` with debounce | Clean, tested utility |
| Pagination math | Manual offset calculations | DBAL `setMaxResults` + `setFirstResult` | Standard DBAL pagination |
| Employee picker in forms | New component | Existing `DynamicFormField.vue` with `type: 'employee'` | Already renders a searchable employee Select |

**Key insight:** The entire PLSH-03 "employee picker on start form" is already built. `DynamicFormField.vue` handles `type: 'employee'`. `FormFieldCollector::injectAssigneeFieldsForDownstream()` already injects `_assignee_for_{nodeId}` fields of type `employee`. The only missing piece is the PHP enum case.

---

## Common Pitfalls

### Pitfall 1: Snapshot vs Live — What Gets Snapshotted

**What goes wrong:** Developer assumes `Task.formSchema` is always populated, but it may be `null` for tasks created manually (not from a workflow event).

**Why it happens:** `Task::create()` accepts `?array $formSchema = null`. Manual tasks have `null` form schema. `TaskController::show()` merges `workflow_context` which has its own `form_schema` from live graph.

**How to avoid:** When serving `form_schema` from snapshot, fall back gracefully: if `Task.formSchema` is null and `workflow_context` exists, fall back to live computation (or return empty schema). The TaskDTO already has `?array $formSchema` property.

**Warning signs:** Frontend shows empty form fields for newly created workflow tasks.

### Pitfall 2: AssignmentStrategy::from() ValueError in AssignmentResolver

**What goes wrong:** `OnTaskNodeActivated` translates `from_variable` to `specific_user` BEFORE `CreateTaskCommand` is dispatched. But if anything calls `AssignmentResolver::resolve('from_variable', ...)` directly (e.g., future code paths, or tests), it throws `ValueError`.

**Why it happens:** `AssignmentStrategy::from($strategy)` will throw `ValueError` for unknown string values. `from_variable` is not in the enum.

**How to avoid:** Add `FromVariable` to the enum immediately. Add a defensive `FromVariable` case to the `AssignmentResolver` match — treat it as Unassigned since resolution already happened upstream.

**Warning signs:** `PHP Fatal error: Uncaught ValueError: 'from_variable' is not a valid backing value for enum App\TaskManager\Domain\ValueObject\AssignmentStrategy`.

### Pitfall 3: PrimeVue DataTable Lazy Pagination — 0-indexed Pages

**What goes wrong:** PrimeVue `@page` event returns `{ page: 0, rows: 20 }` where `page` is 0-indexed. Backend expects 1-indexed.

**How to avoid:** Always add 1 when passing page to backend: `page: event.page + 1`.

**Warning signs:** First page fetches page 2 instead of page 1 from backend, empty results.

### Pitfall 4: Definition Name Search — ILIKE vs LIKE

**What goes wrong:** PostgreSQL `LIKE` is case-sensitive. Searching "Invoice" won't find "invoice".

**How to avoid:** Use `ILIKE` in PostgreSQL for case-insensitive search. DBAL supports it directly in query builder `andWhere('definition_name ILIKE :search')`.

**Warning signs:** Users report that uppercase/lowercase search terms return different results.

### Pitfall 5: Breaking TaskController Response Shape

**What goes wrong:** Changing `ListProcessInstances` endpoint response from `array` to `{ items, total, page, limit }` breaks frontend without corresponding frontend update.

**How to avoid:** Change backend and frontend store in the same plan. Update `processInstanceApi.list()` return type. Update `process-instance.store.ts` to store `total` separately. Update `ProcessInstancesPage.vue` to use `totalRecords`.

**Warning signs:** TypeScript errors in `process-instance.store.ts` or empty instance list after the backend change.

---

## Code Examples

### GetTaskWorkflowContextHandler — After Refactor (PLSH-02)

```php
// Source: direct codebase analysis
// Before: uses FormFieldCollector directly (duplicates FormSchemaBuilder)
// After: delegates to FormSchemaBuilder

final readonly class GetTaskWorkflowContextHandler
{
    public function __construct(
        private WorkflowTaskLinkRepositoryInterface $linkRepository,
        private ProcessInstanceRepositoryInterface $instanceRepository,
        private ProcessDefinitionVersionRepositoryInterface $versionRepository,
        private ProcessDefinitionRepositoryInterface $definitionRepository,
        private FormSchemaBuilder $formSchemaBuilder,  // was: FormFieldCollector
    ) {}

    public function __invoke(GetTaskWorkflowContextQuery $query): ?TaskWorkflowContextDTO
    {
        // ... (link + instance + definition lookups unchanged) ...

        $graph = ProcessGraph::fromSnapshot($version->nodesSnapshot());
        $formSchema = $this->formSchemaBuilder->build($graph, $nodeId);  // single call

        return new TaskWorkflowContextDTO(
            processInstanceId: $link->processInstanceId(),
            processName: $processName,
            nodeName: $nodeName,
            nodeId: $nodeId,
            isCompleted: false,
            formSchema: $formSchema,
        );
    }
}
```

### TaskController::show() — Snapshot Serving (PLSH-01)

```php
// Source: direct codebase analysis of TaskController::show()
public function show(string $organizationId, string $taskId): JsonResponse
{
    $this->authorizer->authorize($organizationId, 'TASK_VIEW');

    $dto = $this->queryBus->ask(new GetTaskQuery($taskId));
    $workflowContext = $this->queryBus->ask(new GetTaskWorkflowContextQuery($taskId));

    $taskData = json_decode(json_encode($dto, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

    // PLSH-01: Prefer snapshotted form_schema from Task entity
    if (null !== $workflowContext && null !== $dto->formSchema) {
        $workflowContext = array_merge(
            json_decode(json_encode($workflowContext, JSON_THROW_ON_ERROR), true),
            ['form_schema' => $dto->formSchema]
        );
    }
    $taskData['workflow_context'] = $workflowContext;

    return new JsonResponse($taskData);
}
```

### ListProcessInstancesQuery — Extended (PLSH-05)

```php
// Source: direct codebase analysis
final readonly class ListProcessInstancesQuery implements QueryInterface
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $search = null,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
```

### ListProcessInstancesHandler — With Search + Pagination (PLSH-05)

```php
// Source: direct codebase analysis
public function __invoke(ListProcessInstancesQuery $query): array
{
    $qb = $this->connection->createQueryBuilder()
        ->select('*')
        ->from('workflow_process_instances_view')
        ->where('organization_id = :orgId')
        ->setParameter('orgId', $query->organizationId)
        ->orderBy('started_at', 'DESC');

    if (null !== $query->status) {
        $qb->andWhere('status = :status')->setParameter('status', $query->status);
    }

    if (null !== $query->search && '' !== $query->search) {
        $qb->andWhere('definition_name ILIKE :search')
           ->setParameter('search', '%' . $query->search . '%');
    }

    // Count total before applying LIMIT/OFFSET
    $countQb = clone $qb;
    $total = (int) $countQb->select('COUNT(*)')->executeQuery()->fetchOne();

    $offset = ($query->page - 1) * $query->limit;
    $qb->setMaxResults($query->limit)->setFirstResult($offset);

    $rows = $qb->executeQuery()->fetchAllAssociative();
    $items = array_map(
        static fn (array $row): ProcessInstanceDTO => ProcessInstanceDTO::fromRow($row),
        $rows,
    );

    return ['items' => $items, 'total' => $total, 'page' => $query->page, 'limit' => $query->limit];
}
```

---

## State of the Art

| Old Approach | Current Approach | Impact |
|--------------|-----------------|--------|
| `GetTaskWorkflowContextHandler` duplicates `FormSchemaBuilder` logic | After PLSH-02: single builder used everywhere | Eliminates silent drift between task creation schema and display schema |
| `Task.formSchema` stored but never served to frontend | After PLSH-01: snapshot served as authoritative source | Form schema now stable across workflow definition changes |
| `AssignmentStrategy` enum missing `FromVariable` | After PLSH-03: complete enum | No more potential `ValueError` on strategy validation |
| Backend returns all instances (no real pagination) | After PLSH-05: LIMIT/OFFSET + total count | Supports lists of hundreds of instances without memory issues |

**Deprecated/outdated:**
- `FormFieldCollector $fieldCollector` dependency in `GetTaskWorkflowContextHandler`: Remove after `FormSchemaBuilder` is injected.

---

## Open Questions

1. **Should `GetTaskWorkflowContextHandler` return `form_schema: null` when the task is completed?**
   - What we know: Currently it returns `['shared_fields' => [], 'actions' => []]` for completed tasks.
   - What's unclear: If we serve the snapshot from `Task.formSchema` instead, should completed task DTOs still expose `form_schema`? (The actions are no longer actionable.)
   - Recommendation: Keep current behavior — serve empty schema for completed tasks. The frontend already checks `is_completed` before rendering action buttons.

2. **Does the `from_variable` case in `AssignmentResolver` need to be a `FromVariable` branch or a guard at the top?**
   - What we know: `OnTaskNodeActivated` always translates `from_variable` to `specific_user` before `CreateTaskCommand`. So `AssignmentResolver` should never see it.
   - Recommendation: Add the `FromVariable` enum case. Add a defensive branch in the `match` that returns `AssignmentResult(Unassigned, ...)` as a guard. Add a PHPUnit test case for this path.

3. **What response shape does the frontend expect for paginated list?**
   - What we know: `processInstanceApi.list()` currently returns `ProcessInstanceDTO[]`. The store assigns `instances.value = await api.list(...)`.
   - Recommendation: Change to `{ items: ProcessInstanceDTO[], total: number }`. Update store to have both `instances` and `total` refs. Update the DataTable to use `lazy + totalRecords`.

---

## Validation Architecture

`workflow.nyquist_validation` is not set in `.planning/config.json` (no nyquist_validation key), so this section is skipped per agent instructions.

However, for completeness — the project has PHPUnit 13 with config at `/Users/leleka/Projects/procivo/backend/phpunit.dist.xml`. Quick run: `cd backend && bin/phpunit tests/Unit`. Vitest exists at `/Users/leleka/Projects/procivo/frontend/vitest.config.ts`.

Existing relevant test files (confirmed present):
- `tests/Unit/Workflow/Application/Service/FormSchemaBuilderTest.php` — covers FormSchemaBuilder
- `tests/Unit/TaskManager/Application/Service/AssignmentResolverTest.php` — covers AssignmentResolver
- `tests/Unit/Workflow/Application/EventHandler/OnTaskNodeActivatedTest.php` — covers from_variable resolution

New tests recommended for Phase 6:
- `GetTaskWorkflowContextHandlerTest.php` — verify FormSchemaBuilder delegation
- `AssignmentResolverTest.php` — add `from_variable` guard test case

---

## Sources

### Primary (HIGH confidence — direct codebase inspection)

- `/Users/leleka/Projects/procivo/backend/src/TaskManager/Domain/ValueObject/AssignmentStrategy.php` — confirmed 4 cases, `FromVariable` missing
- `/Users/leleka/Projects/procivo/backend/src/Workflow/Application/Query/GetTaskWorkflowContext/GetTaskWorkflowContextHandler.php` — confirmed duplicate of FormSchemaBuilder logic
- `/Users/leleka/Projects/procivo/backend/src/Workflow/Application/Service/FormSchemaBuilder.php` — confirmed single `build()` method
- `/Users/leleka/Projects/procivo/backend/src/Workflow/Application/Query/ListProcessInstances/ListProcessInstancesHandler.php` — confirmed: no LIMIT/OFFSET, no search
- `/Users/leleka/Projects/procivo/backend/src/Workflow/Application/Command/CancelProcess/CancelProcessHandler.php` — confirmed: cancel backend exists
- `/Users/leleka/Projects/procivo/backend/src/Workflow/Presentation/Controller/ProcessInstanceController.php` — confirmed: cancel route exists at `POST /{instanceId}/cancel`
- `/Users/leleka/Projects/procivo/frontend/src/modules/workflow/pages/ProcessInstanceDetailPage.vue` — confirmed: cancel button + handler already present
- `/Users/leleka/Projects/procivo/frontend/src/modules/tasks/components/DynamicFormField.vue` — confirmed: `type: 'employee'` renders Select
- `/Users/leleka/Projects/procivo/frontend/src/modules/tasks/components/ProcessContextCard.vue` — confirmed: ProgressBar uses `completedStepCount * 10` (hardcoded bug)
- `/Users/leleka/Projects/procivo/backend/src/TaskManager/Domain/Entity/Task.php` — confirmed: `formSchema` field exists as `?array`
- `/Users/leleka/Projects/procivo/backend/src/TaskManager/Application/Service/AssignmentResolver.php` — confirmed: `::from($strategy)` without FromVariable handling

### Secondary (MEDIUM confidence)

- PrimeVue DataTable lazy mode documentation — standard pattern for server-side pagination with `@page` event (consistent with project's PrimeVue 4 usage)

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — no new libraries, all existing
- Architecture: HIGH — direct codebase inspection, exact file paths and line numbers verified
- Pitfalls: HIGH — identified from actual code paths that would fail

**Research date:** 2026-03-01
**Valid until:** 2026-04-01 (stable — internal codebase, no external API dependency)
