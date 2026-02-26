# Workflow + Tasks Integration Plan

## Status: DRAFT (discussion stage)

## Problem

Workflow Designer (Process Definitions) and Tasks (TaskManager) exist as separate UX flows.
User expectation: "I create a task from a process, work on it, and the process moves forward."
Current reality: disconnected pages, no form rendering, no action buttons, no process context on tasks.

---

## Architecture Decision: Forms per Action

### Why

Different actions on a task require different form fields:
- "Approve" → might need signature, approval note
- "Reject" → only a comment
- "Request clarification" → comment + maybe attachments
- "Complete" (no branching) → work result fields

### Model

Each **outgoing transition** from a task node can define its own `formFields`.
Shared fields (visible for ALL actions) stay on the **task node** config.

```
Task Node config:
  formFields: [...]          ← shared fields (shown always)

Transition "Approve":
  formFields: [...]          ← shown only when this action is selected
  condition_expression: ...  ← for gateway evaluation

Transition "Reject":
  formFields: [...]          ← different fields for this action
  condition_expression: ...
```

### UI Result

```
┌──────────────────────────────────┐
│ Stage: Review Documents          │
│ Process: Employee Onboarding 3/7 │
│                                  │
│ ── Shared fields ──              │
│ Comment: [____________]          │
│                                  │
│ ── Actions ──                    │
│ [OK]  [Incomplete]               │
│                                  │
│ (clicking "Incomplete" may show  │
│  extra fields specific to that   │
│  action before confirming)       │
└──────────────────────────────────┘
```

### Where to store form definitions

**Option A (simpler):** formFields stay on transitions in the Process Definition.
When task is created, the backend includes the outgoing transitions' form schemas in the task metadata.

**Option B:** Separate FormSchema entity. Over-engineering for now.

**Decision: Option A.**

---

## Data Flow

```
1. Process starts
   → WorkflowEngine creates token at Start
   → moves to first Task node
   → OnTaskNodeActivated creates Task in TaskManager
   → Task includes: formFields (shared + per-action from transitions)

2. User opens task in /tasks
   → sees form fields + action buttons
   → fills shared fields
   → clicks action button (e.g. "OK")
   → if action has extra fields → shows them → user fills → confirms

3. User submits action
   → Frontend POST: { action: "OK", formData: { comment: "..." } }
   → Backend: saves formData to ProcessInstance.variables
   → Backend: completes task, token moves forward
   → If next node is XOR → evaluator reads variables → picks path
   → If next node is Task → new Task created → appears in assignee's list

4. Process completes
   → All tokens reach End nodes
   → ProcessInstance status = completed
```

---

## Backend Changes Needed

### 1. ProcessInstance: add `variables` (JSONB)

```php
// ProcessInstance entity
private array $variables = [];  // JSONB column

public function setVariable(string $key, mixed $value): void
public function getVariable(string $key): mixed
public function mergeVariables(array $data): void
```

Migration: add `variables` JSONB column to `workflow_process_instances` table.

### 2. Transition: add `formFields` (JSONB, nullable)

Transitions already have `condition_expression`. Add optional `formFields`:
```php
// Transition entity
private ?array $formFields = null;  // JSONB: FormFieldDefinition[]
```

API: `addTransition` / `updateTransition` accept optional `form_fields`.
Migration: add `form_fields` JSONB column to `workflow_transitions` table.

### 3. CompleteTaskNode: accept form data + action

Current: `CompleteTaskNodeCommand(processInstanceId, tokenId)`
New: `CompleteTaskNodeCommand(processInstanceId, tokenId, action, formData)`

- `action`: the transition/action key chosen by user
- `formData`: Record<string, mixed> — submitted form values
- Handler validates required fields against schema
- Handler merges formData into ProcessInstance.variables
- Handler completes token and moves it forward

### 4. Task creation: include form schemas

`OnTaskNodeActivated` currently creates a Task with title, description, assignee, priority.
Add: include `form_schema` in task metadata — shared fields from node config + per-action fields from outgoing transitions.

```php
// Task metadata (JSONB or separate column)
$formSchema = [
    'shared_fields' => $nodeConfig['formFields'] ?? [],
    'actions' => [
        [
            'key' => 'OK',
            'label' => 'OK',
            'transition_id' => '...',
            'fields' => [...],  // from transition formFields
        ],
        [
            'key' => 'Incomplete',
            'label' => 'Incomplete',
            'transition_id' => '...',
            'fields' => [...],
        ],
    ],
];
```

If task has only ONE outgoing transition (linear flow), show single "Complete" button.
If multiple outgoing transitions, show action buttons.

### 5. XOR Gateway: condition evaluator

Current: `condition_expression` exists but is not evaluated.
Need: `ConditionEvaluator` service that reads ProcessInstance.variables and evaluates expressions.

Simple expression language: `fieldName == 'value'`
- Parse left side (variable name)
- Parse operator (==, !=, >, <)
- Parse right side (literal value)
- Read variable from ProcessInstance.variables
- Return boolean

### 6. Task entity: add workflow metadata

Task in TaskManager needs to show process context.
Add fields or use existing metadata JSONB:
- `process_instance_id` (already in WorkflowTaskLink)
- `process_definition_name` — "Employee Onboarding"
- `node_name` — "Verify Documents"
- `form_schema` — the full form schema (shared + actions)

Could expose via API by joining WorkflowTaskLink with ProcessInstance + Definition.

---

## Frontend Changes Needed

### 1. Task Detail Page: form rendering

New component: `WorkflowTaskForm.vue`
- Renders shared form fields (text, number, date, select, checkbox, textarea)
- Shows action buttons derived from `form_schema.actions`
- Clicking action → if action has extra fields → expand/show them
- Submit → POST to backend with { action, formData }

### 2. Task List: process context

On task cards, show:
- Process name badge (e.g. "Employee Onboarding")
- Stage indicator (e.g. "3/7") — if available
- Different visual styling for process tasks vs standalone tasks

### 3. Start process from Tasks page

- "Create" button dropdown: "Standalone task" | "Start process"
- "Start process" → shows list of published process definitions
- Clicking one → starts process instance → redirects to first task or to task list

### 4. Process Instances page → Monitoring view

Rename/reposition as admin monitoring:
- Shows all running processes with progress
- Where each process is (which node has active tokens)
- Who is blocking (task assigned to X, waiting N days)
- Less prominent in navigation (admin section)

---

## Designer Changes (Transition form fields)

### TransitionPropertyPanel update

Add `FormFieldsBuilder` to `TransitionPropertyPanel.vue`:
- After condition expression section
- "Action form fields" — fields shown when user picks this action
- Same FormFieldsBuilder component already created for task nodes
- Save to transition's `form_fields` via API

### Backend API

`updateTransition` endpoint accepts optional `form_fields: FormFieldDefinition[]`
- Validated same way as node config formFields
- Stored in transition JSONB column

---

## Implementation Order (sessions)

### Session 1: Backend foundation
- [ ] Add `variables` JSONB to ProcessInstance entity + migration
- [ ] Add `form_fields` JSONB to Transition entity + migration
- [ ] Update CompleteTaskNodeCommand to accept formData + action
- [ ] Merge formData into ProcessInstance.variables on completion
- [ ] ConditionEvaluator service for XOR gateway
- [ ] Update WorkflowEngine to use ConditionEvaluator

### Session 2: Task form schema
- [ ] OnTaskNodeActivated: build form_schema from node config + outgoing transitions
- [ ] Store form_schema in Task metadata
- [ ] API: task detail returns form_schema
- [ ] API: task completion accepts { action, formData }
- [ ] Validate required fields on submission

### Session 3: Frontend task form
- [ ] WorkflowTaskForm.vue — dynamic form renderer
- [ ] Action buttons from form_schema.actions
- [ ] Conditional fields per action
- [ ] Submit flow → API call → success feedback
- [ ] Task detail page integration

### Session 4: Integration + Polish
- [ ] "Start process" from Tasks page
- [ ] Process context on task cards (badge, stage)
- [ ] Process Instances page → monitoring view
- [ ] i18n for all new UI elements

---

## Open Questions

1. **Form field types expansion**: Do we need file upload, rich text, user picker? Or text/number/date/select/checkbox/textarea is enough for now?

2. **Task reassignment**: Can a user forward a task to someone else? Or only the originally assigned person can complete it?

3. **Task deadlines**: Should stages have SLA/deadlines? (e.g. "must be completed within 3 days")

4. **Process cancellation**: Can a running process be cancelled? What happens to open tasks?

5. **Parallel task visibility**: When AND gateway creates 3 parallel tasks, should the user see all 3 or only the ones assigned to them?

6. **History/audit**: Should completed form data be visible in task history? Process timeline?

---

## Files Reference

### Existing backend files (to modify):
- `backend/src/Workflow/Domain/Entity/ProcessInstance.php` — add variables
- `backend/src/Workflow/Domain/Entity/Transition.php` — add formFields
- `backend/src/Workflow/Domain/Service/WorkflowEngine.php` — condition evaluation
- `backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php` — form schema
- `backend/src/Workflow/Application/EventHandler/OnWorkflowTaskCompleted.php` — pass formData
- `backend/src/Workflow/Application/Command/CompleteTaskNode/` — accept formData

### Existing frontend files (to modify):
- `frontend/src/modules/workflow/components/TransitionPropertyPanel.vue` — add form fields builder
- `frontend/src/modules/workflow/pages/ProcessInstancesPage.vue` — monitoring view
- `frontend/src/modules/tasks/pages/TasksPage.vue` — start process button
- `frontend/src/modules/tasks/` — task detail with form

### New files to create:
- `backend/src/Workflow/Domain/Service/ConditionEvaluator.php`
- `frontend/src/modules/tasks/components/WorkflowTaskForm.vue`
