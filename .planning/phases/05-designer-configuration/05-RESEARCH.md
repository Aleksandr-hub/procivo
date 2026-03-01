# Phase 5: Designer Configuration - Research

**Researched:** 2026-03-01
**Domain:** Workflow Designer UI (Vue 3 + PrimeVue 4 + vue-flow)
**Confidence:** HIGH

## Summary

Phase 5 closes the design-to-execution loop by wiring the existing Workflow Designer UI components to properly save assignment strategies and per-action form fields to the backend. The critical finding is that **almost all code already exists**. TaskNodeConfig.vue already has the assignment strategy dropdown with dynamic sub-fields (employee/role/department selectors). TransitionPropertyPanel.vue already embeds FormFieldsBuilder.vue for per-transition form fields. The backend Node and Transition entities already accept and persist config JSONB and form_fields JSONB respectively. The save pipeline (designer UI -> API -> entity -> snapshot -> execution) is fully functional.

The work is primarily verification, gap-closing, and polish. The main gaps are: (1) the NodePropertyPanel save flow does not re-fetch the definition after saving, so template-originated config may be stale in the local vue-flow graph state; (2) process templates use legacy `assignee_type`/`assignee_value` keys that the TaskNodeConfig handles via fallback but should be migrated; (3) end-to-end testing of a process designed entirely via the UI has not been done.

**Primary recommendation:** Focus on integration testing and gap-closing rather than new component development. Build a verification plan that designs, publishes, starts, and completes a process end-to-end through the UI.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DSGN-01 | Assignment strategy selector in TaskNodeConfig (dropdown: unassigned, specific_employee, by_role, by_department) | **ALREADY IMPLEMENTED.** TaskNodeConfig.vue lines 35-41 has `strategyOptions` computed with all 4 strategies + `from_variable`. Lines 182-240 render the Select dropdown with dynamic sub-fields. Uses organization stores (employee, role, department) for selector options. Loads data on mount. |
| DSGN-02 | Dynamic sub-fields based on strategy (employee selector, role selector, department selector) | **ALREADY IMPLEMENTED.** TaskNodeConfig.vue lines 194-240: `v-if="assignmentStrategy === 'specific_user'"` shows employee Select with filter, `v-if="assignmentStrategy === 'by_role'"` shows role Select, `v-if="assignmentStrategy === 'by_department'"` shows department Select with tree flattening for hierarchy display. |
| DSGN-03 | Per-transition form field builder in designer (FormFieldsBuilder.vue already exists -- wire to backend save) | **ALREADY IMPLEMENTED.** TransitionPropertyPanel.vue lines 161-165 embeds `<FormFieldsBuilder>` and the `save()` function (line 58-66) sends `form_fields` to the API. TaskNodeConfig.vue lines 245-249 also embeds FormFieldsBuilder for shared (node-level) fields, emitting via `onFieldsUpdate()` which merges into node config. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Vue 3 | 3.5 | Reactive UI framework | Project standard |
| PrimeVue | 4 | UI component library (Select, InputText, Button, etc.) | Project standard |
| @vue-flow/core | latest | BPMN canvas rendering | Already used in WorkflowDesigner.vue |
| Pinia | 3 | State management for org stores | Project standard |
| vue-i18n | latest | i18n for UI labels | Project standard, all labels in uk.json/en.json |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PrimeVue Select | 4 | Dropdown with filter support | Assignment strategy + entity selectors |
| PrimeVue Checkbox | 4 | Boolean field required toggle | FormFieldsBuilder field cards |
| PrimeVue InputText | 4 | Text inputs | Field names, labels, options |
| PrimeVue Divider | 4 | Visual separation | Between config sections |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| PrimeVue Select | PrimeVue AutoComplete | AutoComplete adds server-side search complexity not needed for small org entity lists |
| Flat department list | PrimeVue TreeSelect | TreeSelect would be more natural for hierarchical departments but adds complexity; current flattenDepts approach is already working |

**Installation:**
```bash
# No new packages needed — all dependencies already installed
```

## Architecture Patterns

### Existing Data Flow (Designer -> Backend -> Execution)

```
┌─────────────────────┐
│  WorkflowDesigner    │ ── selectNode ──> NodePropertyPanel
│  (vue-flow canvas)   │ ── selectEdge ──> TransitionPropertyPanel
└─────────────────────┘
         │
         │ API calls on save
         ▼
┌─────────────────────┐
│  NodeController      │ PUT /nodes/{id} → UpdateNodeCommand → Node.update(config: JSONB)
│  TransitionController│ PUT /transitions/{id} → UpdateTransitionCommand → Transition.update(formFields: JSONB)
└─────────────────────┘
         │
         │ On publish
         ▼
┌─────────────────────┐
│  PublishHandler      │ Node.toSnapshot() + Transition.toSnapshot() → ProcessDefinitionVersion.nodesSnapshot
└─────────────────────┘
         │
         │ On process start + token arrives at task node
         ▼
┌─────────────────────┐
│  OnTaskNodeActivated │ → ProcessGraph.nodeConfig(nodeId) reads assignment_strategy, formFields
│                      │ → FormSchemaBuilder.build() reads outgoing transitions' form_fields
│                      │ → Creates Task with formSchema snapshot + assignment resolution
└─────────────────────┘
```

### Pattern 1: Config Emit Pattern (Node Config Components)
**What:** Each node config component receives `config: Record<string, unknown>` prop and emits `update` with the full merged config object.
**When to use:** All node config panels (TaskNodeConfig, TimerNodeConfig, etc.)
**Example:**
```typescript
// Source: TaskNodeConfig.vue (existing pattern)
function buildConfig(extraFields?: Record<string, unknown>): Record<string, unknown> {
  const cfg: Record<string, unknown> = {
    ...props.config,
    task_title_template: taskTitle.value || undefined,
    assignment_strategy: assignmentStrategy.value,
    ...extraFields,
  }
  // Set strategy-specific keys, clean unused ones
  cfg.assignee_employee_id = assignmentStrategy.value === 'specific_user' ? assigneeEmployeeId.value : undefined
  cfg.assignee_role_id = assignmentStrategy.value === 'by_role' ? assigneeRoleId.value : undefined
  cfg.assignee_department_id = assignmentStrategy.value === 'by_department' ? assigneeDepartmentId.value : undefined
  return cfg
}

function onFieldsUpdate(fields: FormFieldDefinition[]) {
  emit('update', buildConfig({ formFields: fields }))
}
```

### Pattern 2: Transition Panel Direct Save
**What:** TransitionPropertyPanel saves directly to the API (unlike node panels which emit to parent). It manages its own state and calls `processDefinitionApi.updateTransition()`.
**When to use:** Transition property editing.
**Example:**
```typescript
// Source: TransitionPropertyPanel.vue (existing pattern)
async function save() {
  await processDefinitionApi.updateTransition(props.orgId, props.definition.id, props.edge.id, {
    source_node_id: props.edge.source,
    target_node_id: props.edge.target,
    name: name.value || null,
    action_key: actionKey.value || null,
    condition_expression: conditionExpression.value || null,
    form_fields: formFields.value.length > 0 ? formFields.value : undefined,
  })
}
```

### Pattern 3: Organization Store Lazy Loading
**What:** TaskNodeConfig lazy-loads employee/role/department lists from org stores on mount, only if not already loaded.
**When to use:** Any designer component that needs org entity selectors.
**Example:**
```typescript
// Source: TaskNodeConfig.vue (existing pattern)
onMounted(() => {
  if (empStore.employees.length === 0) empStore.fetchEmployees(props.orgId)
  if (roleStore.roles.length === 0) roleStore.fetchRoles(props.orgId)
  if (deptStore.tree.length === 0) deptStore.fetchTree(props.orgId)
})
```

### Pattern 4: Backend Config JSONB Pass-Through
**What:** The backend treats node `config` as opaque JSONB -- no schema validation on config contents. The frontend is the source of truth for config structure. Backend just stores and returns it.
**When to use:** Node config fields are frontend-defined; backend passes them through to ProcessGraph at execution time.
**Why it matters:** No backend changes needed for new config fields in the designer. The existing Node entity `config: array<string, mixed>` and Transition `formFields: array<int, array<string, mixed>>|null` already handle arbitrary structures.

### Anti-Patterns to Avoid
- **Re-implementing what exists:** All three DSGN requirements are already implemented in the codebase. Do NOT create new components or rewrite existing ones. Focus on verification and gap-closing.
- **Backend config schema validation:** Do NOT add Symfony validation constraints on node config JSONB. The contract is frontend-to-execution, not frontend-to-backend-schema. Adding backend validation would break the flexible config pattern used across all node types.
- **Bypassing the snapshot pipeline:** Do NOT try to read config directly from Node entities at execution time. Always go through ProcessDefinitionVersion.nodesSnapshot -> ProcessGraph. This ensures process instances use the published version, not live edits.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Assignment strategy UI | New component from scratch | Existing TaskNodeConfig.vue | Already has dropdown + dynamic sub-fields + org store integration |
| Form field builder | New form builder component | Existing FormFieldsBuilder.vue | Already handles add/remove fields, type selection, label-to-name transliteration, options for selects |
| Department hierarchy display | Custom tree component | Existing flattenDepts() in TaskNodeConfig.vue | Already flattens DepartmentTreeDTO with indent levels |
| Org entity loading | Manual API calls in components | Existing Pinia stores (employee, role, department) | Already cached, with loading states |

**Key insight:** This phase is a verification/integration phase, not a development phase. The infrastructure from Phases 1-4 already covers the designer configuration requirements.

## Common Pitfalls

### Pitfall 1: Stale Local Graph State After Node Save
**What goes wrong:** When NodePropertyPanel saves, it calls `updateNodeData()` in WorkflowDesigner.vue which updates the API AND the local `node.data`. But if the user loads a template and then edits, the local state and API state may drift because templates are applied by creating nodes via API and syncing. The sync only happens on initial load or definition re-fetch.
**Why it happens:** `syncFromDefinition()` runs on `watch(() => props.definition)` but `props.definition` is the store's `currentDefinition` which is NOT re-fetched after each node/transition save.
**How to avoid:** Verify that after saving node config (with assignment strategy or form fields), the config persists across page reload. If not, the local `node.data.config` update in `updateNodeData()` must correctly capture the full config including formFields.
**Warning signs:** Config appears saved but is lost on page refresh.

### Pitfall 2: Legacy Template Config Keys
**What goes wrong:** Process templates (process-templates.ts) use legacy keys like `assignee_type: 'role'` and `assignee_value: 'HR'` instead of the new `assignment_strategy: 'by_role'` and `assignee_role_id: '<uuid>'`. When templates are applied, these legacy keys are stored in node config.
**Why it happens:** Templates were written before the assignment strategy refactor.
**How to avoid:** TaskNodeConfig.vue already handles this with fallback logic (lines 92-107) that maps old values to new. For full correctness, templates should be updated to use new keys, but since template values are string role names (not UUIDs), they can't directly map to `assignee_role_id`. The legacy handler in OnTaskNodeActivated also falls back correctly.
**Warning signs:** Templates show "Unassigned" strategy even though they have `assignee_type` configured.

### Pitfall 3: formFields Key in Config vs Transition
**What goes wrong:** Confusion between node-level `config.formFields` (shared fields for all actions on that task) and transition-level `form_fields` (action-specific fields per transition). Both use FormFieldsBuilder but serve different purposes.
**Why it happens:** Both are arrays of `FormFieldDefinition` stored in different places.
**How to avoid:** TaskNodeConfig embeds FormFieldsBuilder for shared fields (stored in `node.config.formFields`). TransitionPropertyPanel embeds FormFieldsBuilder for action-specific fields (stored in `transition.form_fields`). FormSchemaBuilder.build() merges both: `shared_fields` from node config + per-action `form_fields` from transitions.
**Warning signs:** Fields appear in wrong actions, or shared fields don't show across all actions.

### Pitfall 4: Missing action_key on Transitions
**What goes wrong:** If a transition has form_fields configured but no action_key, the FormSchemaBuilder defaults to 'complete' as the action key. Multiple transitions without action_keys would all map to the same action, causing confusion.
**Why it happens:** action_key is optional in the TransitionPropertyPanel.
**How to avoid:** During end-to-end testing, verify that each outgoing transition from a task node has a unique action_key when form_fields are configured. Consider adding a validation warning in useCanvasValidation.ts for transitions with form_fields but no action_key.
**Warning signs:** Only one action button appears when multiple were expected.

### Pitfall 5: Config Not Persisting formFields on Node Save
**What goes wrong:** When the user clicks Save on NodePropertyPanel, the config emitted by TaskNodeConfig includes `formFields`. The `buildConfig()` function merges via `...props.config` spread. But if `onFieldsUpdate` is called separately from `emitConfig`, the two may not merge correctly.
**Why it happens:** `onFieldsUpdate` calls `buildConfig({ formFields: fields })` which spreads the existing config first, then overrides with the fields. This should work correctly. But if `emitConfig()` is called for a strategy change AFTER fields were set, it does NOT include `formFields` in the override -- it relies on `props.config` spread which should still have them. This is correct as long as the parent (NodePropertyPanel) stores the full config in its local ref.
**How to avoid:** Verify the flow: TaskNodeConfig emits config with formFields -> NodePropertyPanel.onConfigUpdate stores full config in ref -> on Save, full config (with formFields) is sent to API. Test specifically by: (1) add form fields, (2) change strategy, (3) save, (4) verify fields persisted.
**Warning signs:** Form fields disappear after changing assignment strategy.

## Code Examples

Verified patterns from existing codebase:

### Node Config Save Flow (WorkflowDesigner.vue)
```typescript
// Source: WorkflowDesigner.vue lines 184-201
async function updateNodeData(nodeId: string, data: { name: string; description: string | null; config: Record<string, unknown> }) {
  if (!isDraft.value) return
  const node = nodes.value.find((n) => n.id === nodeId)
  if (!node) return
  try {
    await processDefinitionApi.updateNode(props.orgId, props.definition.id, nodeId, {
      name: data.name,
      description: data.description,
      config: data.config,
      position_x: node.position.x,
      position_y: node.position.y,
    })
    node.data = { ...node.data, label: data.name, description: data.description, config: data.config }
    toast.add({ severity: 'success', summary: t('common.success'), detail: t('workflow.nodeUpdated'), life: 2000 })
  } catch (error: unknown) {
    toast.add({ severity: 'error', summary: t('common.error'), detail: getApiErrorMessage(error, t('workflow.operationFailed')), life: 5000 })
  }
}
```

### Backend Node Config Storage (NodeController.php)
```php
// Source: NodeController.php lines 49-62
$this->commandBus->dispatch(new UpdateNodeCommand(
    nodeId: $nodeId,
    name: $data['name'] ?? '',
    description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null,
    config: isset($data['config']) && \is_array($data['config']) ? $data['config'] : [],
    positionX: isset($data['position_x']) && is_numeric($data['position_x']) ? (float) $data['position_x'] : 0.0,
    positionY: isset($data['position_y']) && is_numeric($data['position_y']) ? (float) $data['position_y'] : 0.0,
));
```

### Execution-Time Config Reading (OnTaskNodeActivated.php)
```php
// Source: OnTaskNodeActivated.php lines 36-68
$taskConfig = $event->taskConfig;

// Assignment strategy from config
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

### FormSchemaBuilder Shared + Per-Action Fields (FormSchemaBuilder.php)
```php
// Source: FormSchemaBuilder.php lines 24-52
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
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `assignee_type` + `assignee_value` (legacy) | `assignment_strategy` + `assignee_employee_id` / `assignee_role_id` / `assignee_department_id` | Phase 2 (Feb 2026) | TaskNodeConfig handles both with fallback; templates still use legacy keys |
| Form fields only on transitions | Form fields on BOTH nodes (shared) and transitions (per-action) | Phase 2 (Feb 2026) | FormSchemaBuilder merges both; FormFieldsBuilder is reused in both panels |
| No designer config for assignment | Full designer config with org entity selectors | Phase 2-4 (Feb 2026) | TaskNodeConfig already implements all selectors |

**Deprecated/outdated:**
- `assignee_type` / `assignee_value` keys: Replaced by `assignment_strategy` / `assignee_employee_id` / `assignee_role_id` / `assignee_department_id`. Fallback exists but templates should be updated.

## Open Questions

1. **Template Migration**
   - What we know: 6 process templates use legacy `assignee_type`/`assignee_value` keys (e.g., `assignee_type: 'role', assignee_value: 'HR'`). These are string names, not UUIDs.
   - What's unclear: Should templates be updated to use new keys? Template role/dept values are names like "HR", "Manager", not UUIDs — they can't directly reference real org entities.
   - Recommendation: Leave templates as-is for now. They serve as starting points that users customize. The fallback logic in TaskNodeConfig handles display correctly. Templates are not used at execution time (processes are published from real definitions). Low priority.

2. **Canvas Validation for Form Fields**
   - What we know: useCanvasValidation.ts validates graph structure (start/end nodes, connectivity). It does NOT validate config completeness (e.g., "task node has transitions with form_fields but no action_key").
   - What's unclear: Should Phase 5 add config-level validation warnings?
   - Recommendation: Add a warning for transitions with form_fields but missing action_key. This prevents the "all actions map to 'complete'" pitfall. Low effort, high value.

3. **End-to-End Flow Verification**
   - What we know: Individual components (TaskNodeConfig, FormFieldsBuilder, TransitionPropertyPanel) work. Backend save pipeline works. Execution pipeline works.
   - What's unclear: Has the FULL flow been tested: create process in designer -> configure assignment + form fields -> publish -> start -> fill forms -> complete?
   - Recommendation: This is the core of Phase 5. Create a structured end-to-end test plan.

## Sources

### Primary (HIGH confidence)
- Codebase analysis: `frontend/src/modules/workflow/components/TaskNodeConfig.vue` - Assignment strategy UI already implemented
- Codebase analysis: `frontend/src/modules/workflow/components/FormFieldsBuilder.vue` - Form field builder already implemented
- Codebase analysis: `frontend/src/modules/workflow/components/TransitionPropertyPanel.vue` - Per-transition form fields already wired
- Codebase analysis: `frontend/src/modules/workflow/components/NodePropertyPanel.vue` - Node config save flow
- Codebase analysis: `frontend/src/modules/workflow/components/WorkflowDesigner.vue` - Canvas + panel integration
- Codebase analysis: `backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php` - Execution reads config
- Codebase analysis: `backend/src/Workflow/Application/Service/FormSchemaBuilder.php` - Schema build from config
- Codebase analysis: `backend/src/Workflow/Application/Command/PublishProcessDefinition/PublishProcessDefinitionHandler.php` - Snapshot pipeline
- Codebase analysis: `backend/src/Workflow/Domain/Entity/Node.php` - Config JSONB storage
- Codebase analysis: `backend/src/Workflow/Domain/Entity/Transition.php` - FormFields JSONB storage

### Secondary (MEDIUM confidence)
- Previous phase research: `.planning/phases/02-form-schema-and-assignment/02-RESEARCH.md` - Assignment strategy architecture decisions

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All components already exist in the codebase, no new libraries needed
- Architecture: HIGH - Full pipeline verified from designer UI through backend to execution
- Pitfalls: HIGH - Identified from direct code analysis of existing implementation

**Research date:** 2026-03-01
**Valid until:** 2026-03-31 (stable -- no external dependencies, all internal code)
