# Phase 4: Frontend Task Integration - Research

**Researched:** 2026-02-28
**Domain:** Vue 3 + PrimeVue 4 frontend — task detail UI, dynamic forms, process context visualization
**Confidence:** HIGH

## Summary

Phase 4 transforms the existing `TaskDetailContent.vue` (a flat, single-column component with basic properties grid and tabs) into a production-quality 2-column task detail page matching the Figma Make prototype. The core work is: (1) restructuring layout into main content + sidebar, (2) enhancing ActionFormDialog with Zod 4 validation, comment field, and next assignment selector, (3) building custom Process Context Card and My Path Stepper components, (4) adding pool task banner with candidate avatars, and (5) enriching task cards with process context badges.

The existing codebase has strong foundations: `DynamicFormField.vue` supports 7 field types, `ActionFormDialog.vue` handles shared + action-specific fields, `StatusDropdownButton.vue` manages action selection, and the task store already has `executeAction`, `claimTask`, `unclaimTask` methods. The frontend API calls `/execute-action` but the backend endpoint was renamed to `/complete` in Phase 3 — this URL mismatch must be fixed.

**Primary recommendation:** Refactor `TaskDetailContent.vue` into smaller composable components (ProcessContextCard, MyPathStepper, PoolTaskBanner, sidebar cards), add Zod 4 dynamic schema builder for form validation, fix API endpoint mismatch, and derive stepper data from the existing process history events API.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- 2-column layout: main content (2/3) + sidebar (1/3), matching Figma prototype
- Sidebar hides on mobile (responsive breakpoint)
- Panel mode (embedded right of task list) shows compact view: no sidebar, no stepper, no Process Data Card
- Full page mode shows complete 2-column layout with all components
- "Expand" button in panel mode opens full page view
- Sidebar Content (all in v1): Assignment card, Status & Priority card, Dates card, Time Tracking card, Watchers card, Related Tasks card, Creator card, Labels card, SLA card (placeholder — show deadline only)
- My Path Stepper: Custom component (not PrimeVue Stepper) matching Figma prototype visual style
- Stepper shows only completed + current steps (no upcoming — unknown due to XOR gateways/branching)
- Stepper visual style: green checkmarks (completed), pulsing blue circle (current), green lines between completed, no dashed lines
- Hover/click on completed step shows tooltip: who executed, when, which action
- Stepper adaptive: 2 modes only — full stepper (fits) or horizontal scroll (overflow). No modal for 20+
- Stepper placed in main content area, below Process Context Card
- Process Context Card: Gradient card (purple/blue tones), 3 sections in row: process info | current stage | progress + navigation
- Progress display: "Крок X" without total (no "/Y") — total unknown with branching
- Progress bar proportional to completed steps (not percentage of total)
- "Переглянути процес" button opens ProcessInstanceDetailPage in new tab (target=_blank)
- "Наступний крок" hint removed (unknown with branching)
- Process Data Card: Key-value grid of process variables, "Показати все (N)" expandable if > 8
- Subtasks: Checklist with checkboxes, progress bar, assignee avatars, inline add
- Action Dialog: Modal dialog (PrimeVue Dialog), sections: dynamic form fields → comment → next assignment selector
- "Next Assignment" section: Fixed assignment = read-only info block; User choice = radio buttons + Select
- Button styling: success for approve, destructive for reject, outline for others
- Zod-based validation from form_schema; inline errors (red text + red border); triggered on submit, real-time on blur after first attempt
- Field types: text, textarea, number, date, select, checkbox
- After action submit: Success toast, "Етап завершено" message, task disappears from active list on next refresh, user stays on detail page
- Task list: Workflow tasks show purple icon (pi-sitemap); process context line; Pool Task badge
- Figma Make source code as reference: React+Tailwind → adapt to Vue 3 + PrimeVue 4

### Claude's Discretion
- Action buttons placement pattern (sticky header vs inline in header section)
- Activity Stream vs separate tabs approach
- Exact spacing, typography, and color values (use PrimeVue design tokens)
- Loading skeleton designs
- Error state handling for API failures
- Empty states for sidebar cards without data
- Transition animations between states

### Deferred Ideas (OUT OF SCOPE)
- "Мої процеси" (My Processes) view — process-centric view, its own phase
- Start Process Dialog (FEND-V2-02)
- Real-time updates via Mercure SSE (FEND-V2-03)
- SLA indicators full implementation (FEND-V2-05) — sidebar placeholder only
- Rich text description editor (FEND-V2-11)
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| FEND-01 | Task detail page renders form_schema as dynamic form using DynamicFormField.vue | Existing `DynamicFormField.vue` supports 7 field types. `TaskDetailContent.vue` already derives `sharedFields` and `availableActions` from `workflow_context.form_schema`. Layout restructuring adds these to main content area. |
| FEND-02 | Action buttons displayed from form_schema.actions | Existing `StatusDropdownButton.vue` + `availableActions` computed already handles this. Enhancement: add approve/reject/outline styling per action type. |
| FEND-03 | ActionFormDialog opens on action click — shows action-specific + shared + comment | Existing `ActionFormDialog.vue` handles shared + action fields. Enhancement: add comment textarea, next assignment selector section. |
| FEND-04 | Frontend form validation with Zod schema built from form_schema | Zod 4.3.6 already in `package.json`. Build dynamic schema from `FormFieldDefinition[]` using `z.object()` + field-type mapping. Use `safeParse` + `z.flattenError` for per-field errors. |
| FEND-05 | Form submission calls POST /tasks/{id}/complete | **CRITICAL:** Frontend API still calls `/execute-action` but backend renamed to `/complete` in Phase 3. Must update `task.api.ts` endpoint URL. Store action already wired. |
| FEND-06 | Pool task banner with claim/unclaim and candidate context | Existing claim/unclaim logic in `TaskDetailContent.vue`. Enhancement: redesign as gradient banner with overlapping avatar circles per Figma prototype. |
| FEND-07 | Process context badge on task cards in list | Existing `TaskCard.vue` already shows `workflow_summary.process_name → node_name`. Enhancement: add purple icon, "Pool Task" badge, TASK-ID prefix. |
| FEND-08 | Process history timeline tab | Existing `ProcessHistoryTimeline.vue` fully functional with Timeline component. No changes needed — already wired into task detail tabs. |
| FEND-09 | Process Context Card | New component: gradient card with process info, current stage, progress bar, navigation button. Data from `workflow_context` (process_name, node_name, process_instance_id). Step count derived from history events. |
| FEND-10 | My Path Stepper | New custom component: derive completed steps from process history events (filter `workflow.task_node.activated` + `workflow.token.completed`). Cross-reference with graph nodes for names. Custom HTML/CSS (not PrimeVue Stepper). |
| FEND-11 | Process navigation — "View Full Process" button | Simple router-link to `process-instance-detail` route. Already exists at `/organizations/:orgId/process-instances/:instanceId`. Add `target=_blank` per user decision. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Vue 3 | 3.5.28 | Component framework | Already in project |
| PrimeVue | 4.5.4 | UI component library | Already in project, auto-imported |
| Pinia | 3.0.4 | State management | Already in project |
| vue-router | 5.0.2 | Routing | Already in project |
| vue-i18n | 12.0.0-alpha.3 | Internationalization | Already in project |
| Zod | 4.3.6 | Schema validation | Already in package.json but unused. Zod 4 API: `z.object()`, `safeParse()`, `z.flattenError()` |
| axios | 1.13.5 | HTTP client | Already in project via `http-client` |

### Supporting (already available, no new installs)
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PrimeIcons | 7.0.0 | Icon set | pi-sitemap, pi-check-circle, pi-users, pi-calendar, pi-arrow-left |
| sass-embedded | 1.97.3 | Scoped CSS | All component styles |

### No New Dependencies Required
All required libraries are already installed. Zod 4.3.6 is in `package.json` but has not been imported in any component yet. The first usage will be in the `buildZodSchema()` utility.

**Installation:** None needed. All dependencies present.

## Architecture Patterns

### Component Hierarchy (Refactored TaskDetail)
```
TaskDetailFullPage.vue (mode="full")
├── TaskDetailContent.vue (orchestrator — fetches data, manages state)
│   ├── TaskDetailHeader.vue (title, breadcrumb, action buttons)
│   ├── PoolTaskBanner.vue (gradient banner, avatar circles, claim/unclaim)
│   ├── ProcessContextCard.vue (gradient card, process info, progress)
│   ├── MyPathStepper.vue (custom horizontal stepper)
│   ├── ProcessDataCard.vue (key-value variable grid)
│   ├── SubtasksList.vue (checklist, progress bar)
│   ├── TaskDescription.vue (description section)
│   ├── ActivityTabs.vue (comments, attachments, history...)
│   └── TaskDetailSidebar.vue (sidebar cards)
│       ├── AssignmentCard.vue
│       ├── StatusPriorityCard.vue
│       ├── DatesCard.vue
│       ├── TimeTrackingCard.vue (v1: display only)
│       ├── WatchersCard.vue (v1: display only)
│       ├── RelatedTasksCard.vue
│       ├── CreatorCard.vue
│       ├── LabelsCard.vue
│       └── SLACard.vue (placeholder)
└── ActionFormDialog.vue (modal with Zod validation + comment + assignment)
    └── DynamicFormField.vue (per-field renderer)

TaskDetailPanel.vue (mode="panel")
├── TaskDetailContent.vue (compact: no sidebar, no stepper)
```

### Pattern 1: Dynamic Zod Schema Builder
**What:** Build Zod validation schema at runtime from `FormFieldDefinition[]`
**When to use:** Before form submission in ActionFormDialog
**Example:**
```typescript
// Source: Zod 4 official docs (https://zod.dev/api)
import { z } from 'zod'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

export function buildZodSchema(fields: FormFieldDefinition[]): z.ZodObject<Record<string, z.ZodType>> {
  const shape: Record<string, z.ZodType> = {}

  for (const field of fields) {
    let schema: z.ZodType

    switch (field.type) {
      case 'text':
      case 'textarea':
        schema = field.required ? z.string().min(1) : z.string().optional()
        break
      case 'number':
        schema = field.required ? z.number() : z.number().optional()
        break
      case 'date':
        schema = field.required ? z.date() : z.date().optional()
        break
      case 'select':
      case 'employee':
        schema = field.required ? z.string().min(1) : z.string().optional()
        break
      case 'checkbox':
        schema = z.boolean()
        break
      default:
        schema = z.unknown()
    }

    shape[field.name] = schema
  }

  return z.object(shape)
}

// Usage in ActionFormDialog
const schema = buildZodSchema([...sharedFields, ...action.formFields])
const result = schema.safeParse(formData)
if (!result.success) {
  const flat = z.flattenError(result.error)
  // flat.fieldErrors = { fieldName: ['Error message'] }
}
```

### Pattern 2: Stepper Data Derivation from History Events
**What:** Extract completed step sequence from process history events for MyPathStepper
**When to use:** When rendering the stepper on task detail page
**Example:**
```typescript
// Derive stepper steps from process events + graph
interface StepperStep {
  nodeId: string
  nodeName: string
  status: 'completed' | 'current'
  completedAt?: string
  completedBy?: string  // derived from task assignment data
  actionLabel?: string  // from VariablesMerged event action_key
}

function deriveSteps(
  events: ProcessEventDTO[],
  graph: ProcessInstanceGraphDTO,
  currentNodeId: string
): StepperStep[] {
  const taskNodes = graph.nodes.filter(n => n.type === 'task')
  const nodeNameMap = new Map(taskNodes.map(n => [n.id, n.name]))

  // Filter task_node.activated events (chronological order)
  const activatedEvents = events
    .filter(e => e.event_type === 'workflow.task_node.activated')
    .sort((a, b) => new Date(a.occurred_at).getTime() - new Date(b.occurred_at).getTime())

  const completedNodeIds = new Set(
    events
      .filter(e => e.event_type === 'workflow.token.completed')
      .map(e => e.payload.node_id as string)
  )

  return activatedEvents.map(event => {
    const nodeId = event.payload.node_id as string
    const isCurrent = nodeId === currentNodeId && !completedNodeIds.has(nodeId)
    return {
      nodeId,
      nodeName: (event.payload.node_name as string) ?? nodeNameMap.get(nodeId) ?? nodeId,
      status: isCurrent ? 'current' : 'completed',
      completedAt: isCurrent ? undefined : event.occurred_at,
    }
  })
}
```

### Pattern 3: Conditional Layout Based on Mode
**What:** Same `TaskDetailContent` component renders differently in panel vs full mode
**When to use:** Task detail with panel/full page variants
**Example:**
```vue
<template>
  <div class="task-detail" :class="{ 'two-column': mode === 'full' }">
    <main class="main-content">
      <TaskDetailHeader ... />
      <PoolTaskBanner v-if="task.isPoolTask && !task.assigneeId" ... />
      <ProcessContextCard v-if="task.workflow_context && mode === 'full'" ... />
      <MyPathStepper v-if="task.workflow_context && mode === 'full'" ... />
      <ProcessDataCard v-if="processVariables.length && mode === 'full'" ... />
      <!-- Description, Subtasks, Activity tabs -->
    </main>
    <aside v-if="mode === 'full'" class="sidebar">
      <TaskDetailSidebar ... />
    </aside>
  </div>
</template>

<style scoped>
.two-column {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 1.5rem;
}
@media (max-width: 1024px) {
  .two-column { grid-template-columns: 1fr; }
  .sidebar { display: none; }
}
</style>
```

### Pattern 4: Action Button Styling by Type
**What:** Map action keys to visual severity for PrimeVue buttons
**When to use:** StatusDropdownButton and ActionFormDialog submit button
```typescript
function actionSeverity(actionKey: string): string {
  const key = actionKey.toLowerCase()
  if (key.includes('approve') || key.includes('accept') || key.includes('confirm')) return 'success'
  if (key.includes('reject') || key.includes('decline') || key.includes('cancel')) return 'danger'
  return 'secondary' // outline style for others
}
```

### Anti-Patterns to Avoid
- **Monolithic TaskDetailContent:** The current 674-line component must be split into smaller, focused components. Do NOT add more logic to the existing file.
- **Hardcoded gradient colors:** Use CSS custom properties / PrimeVue design tokens so theming works.
- **Fetching graph data on every render:** The stepper needs graph + history data — fetch once on mount, not per-render. Use the already-available `processInstanceApi.history()` and `processInstanceApi.graph()`.
- **Direct Zod import in multiple components:** Create a single `buildZodSchema` utility function, import it from the dialog only.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Form schema validation | Custom if/else chains per field | Zod 4 dynamic schema builder (`z.object` + `safeParse`) | Zod handles edge cases (null, undefined, type coercion), provides standardized error format via `flattenError` |
| Per-field error display | Manual error tracking per field name | Zod `z.flattenError(result.error).fieldErrors` → `Record<string, string[]>` | Single source of truth for all validation errors |
| Horizontal stepper | PrimeVue Stepper component | Custom HTML/CSS div-based stepper | User explicitly chose custom (not PrimeVue Stepper). PrimeVue Stepper is wizard-oriented (StepPanel content), not status display. Our stepper is read-only status visualization. |
| Timeline display | Custom event list | PrimeVue Timeline component | Already used in `ProcessHistoryTimeline.vue` — proven pattern |
| Toast notifications | Custom notification system | PrimeVue `useToast` | Already used throughout codebase |
| Modal dialogs | Custom overlay | PrimeVue Dialog | Already used in ActionFormDialog, TaskCreateDialog |
| Progress bar | Custom CSS bar | PrimeVue ProgressBar | Already auto-imported and available |

**Key insight:** The existing codebase already uses PrimeVue for all UI primitives. The only custom component is the stepper (user-mandated), everything else should leverage PrimeVue.

## Common Pitfalls

### Pitfall 1: API URL Mismatch (execute-action vs complete)
**What goes wrong:** Frontend calls `/execute-action`, backend expects `/complete`. All action submissions fail with 404.
**Why it happens:** Phase 3 renamed the backend endpoint but the frontend API was not updated.
**How to avoid:** Update `task.api.ts` line 50: change `/execute-action` to `/complete`. Also update `ExecuteActionPayload` type name if desired.
**Warning signs:** Any workflow action submission returning 404 or "route not found".

### Pitfall 2: Zod 4 Import Syntax
**What goes wrong:** Using Zod 3 import patterns with Zod 4 API.
**Why it happens:** Most online examples still show Zod 3. Zod 4 changed error customization API.
**How to avoid:** Use `import { z } from 'zod'` (same as v3). Use `z.flattenError(error)` (new in v4, replaces `error.flatten()`). Use `error` param instead of `message` for custom error messages.
**Warning signs:** TypeScript errors on `.flatten()` method or `required_error` / `invalid_type_error` params.

### Pitfall 3: Stepper Data from Events (incomplete history)
**What goes wrong:** History events may not contain all the data needed for stepper tooltips (who executed, which action).
**Why it happens:** `TaskNodeActivatedEvent` has node_name but not executor name. `VariablesMergedEvent` has action_key but not human-readable label. Executor info is on the Task entity, not the event.
**How to avoid:** For v1, show node_name + timestamp in tooltip. Full executor name requires correlating tasks with events (possible but adds complexity — defer to enhancement). The `occurred_at` timestamp is always available.
**Warning signs:** Empty tooltips on stepper steps.

### Pitfall 4: Sidebar Cards Without Backend Endpoints
**What goes wrong:** Designing UI for Time Tracking, Watchers, Subtasks, Related Tasks without backend support.
**Why it happens:** These are v2 backend features but user wants sidebar cards in v1.
**How to avoid:** Build sidebar cards as display-only components with placeholder/mock data or "coming soon" state. Time Tracking: show estimate from task.estimatedHours. Watchers: show creator only. Related Tasks: show tasks from same process via workflow_summary batch. Subtasks: fully new feature — needs task-subtask relationship (not in current schema).
**Warning signs:** Trying to build CRUD for features that lack backend endpoints.

### Pitfall 5: Process Data Card Variable Format
**What goes wrong:** Displaying raw namespaced variable keys like `node_abc123_approved_amount` to users.
**Why it happens:** Variables are stored with node ID prefix for collision prevention.
**How to avoid:** Strip namespace prefix before display. Map variable keys to field labels using the form_schema field definitions from history. The `ProcessHistoryTimeline.vue` already implements label mapping in `applyLabels()` — reuse that pattern.
**Warning signs:** Cryptic variable names appearing in Process Data Card.

### Pitfall 6: Reactivity When Navigating Between Tasks
**What goes wrong:** Stepper and process context show stale data from previous task when clicking a different task in the list.
**Why it happens:** Task detail component watches `taskId` prop but stepper/context data is fetched separately.
**How to avoid:** Use `watch(taskId, ...)` to re-fetch all process context data (history, graph) when task changes. Clear previous data immediately on taskId change. The existing pattern in `TaskDetailContent.vue` already watches taskId — extend the watcher.
**Warning signs:** Stepper from task A appearing briefly when viewing task B.

## Code Examples

### Example 1: Updated API Call (fix execute-action → complete)
```typescript
// Source: Backend TaskController.php — #[Route('/{taskId}/complete', ...)]
// File: frontend/src/modules/tasks/api/task.api.ts

// BEFORE (broken):
executeAction(orgId: string, taskId: string, data: ExecuteActionPayload): Promise<MessageResponse> {
  return httpClient
    .post(`/organizations/${orgId}/tasks/${taskId}/execute-action`, data)
    .then((r) => r.data)
}

// AFTER (fixed):
completeTask(orgId: string, taskId: string, data: ExecuteActionPayload): Promise<MessageResponse> {
  return httpClient
    .post(`/organizations/${orgId}/tasks/${taskId}/complete`, data)
    .then((r) => r.data)
}
```

### Example 2: Pool Task Banner Component Structure
```vue
<!-- Source: Figma prototype img_1.png -->
<template>
  <div class="pool-banner">
    <div class="banner-content">
      <div class="banner-info">
        <i class="pi pi-users" />
        <div>
          <strong>Pool Task</strong>
          <span>{{ candidateDescription }} · {{ candidateCount }} {{ t('tasks.candidates') }}</span>
        </div>
      </div>
      <div class="banner-actions">
        <Button :label="t('tasks.assignTo')" text size="small" @click="showAssignPicker = true" />
        <Button :label="t('tasks.claimTask')" icon="pi pi-user-plus" @click="emit('claim')" />
      </div>
    </div>
    <div class="candidate-avatars">
      <!-- Overlapping avatar circles -->
      <Avatar v-for="c in visibleCandidates" :key="c.id" :label="c.initials" shape="circle" />
      <span v-if="overflowCount > 0" class="avatar-overflow">+{{ overflowCount }}</span>
    </div>
  </div>
</template>
```

### Example 3: Process Context Card
```vue
<!-- Source: Figma prototype img_1.png — gradient card -->
<template>
  <div class="process-context-card">
    <div class="context-section">
      <i class="pi pi-sitemap context-icon" />
      <div>
        <small>{{ t('process.processLabel') }}</small>
        <strong>{{ processName }}</strong>
      </div>
    </div>
    <div class="context-section">
      <div>
        <small>{{ t('process.currentStage') }}</small>
        <strong class="stage-name">{{ currentStageName }}</strong>
      </div>
    </div>
    <div class="context-section">
      <div>
        <small>{{ t('process.step', { n: completedStepCount }) }}</small>
        <ProgressBar :value="progressPercent" :show-value="false" class="context-progress" />
      </div>
      <a :href="processUrl" target="_blank" class="view-process-link">
        <i class="pi pi-external-link" />
        {{ t('process.viewProcess') }}
      </a>
    </div>
  </div>
</template>

<style scoped>
.process-context-card {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, var(--p-primary-50) 0%, var(--p-purple-50) 100%);
  border: 1px solid var(--p-primary-100);
  border-radius: var(--p-border-radius);
  margin-bottom: 1rem;
}
</style>
```

### Example 4: My Path Stepper (custom component)
```vue
<!-- Source: Figma prototype img_1.png — stepper with green checks -->
<template>
  <div class="my-path-stepper" ref="stepperRef">
    <div
      v-for="(step, index) in steps"
      :key="step.nodeId"
      class="stepper-step"
      :class="step.status"
    >
      <div class="step-connector" v-if="index > 0" :class="{ completed: step.status === 'completed' }" />
      <div
        class="step-circle"
        :class="step.status"
        v-tooltip="step.status === 'completed' ? tooltipContent(step) : undefined"
      >
        <i v-if="step.status === 'completed'" class="pi pi-check" />
        <span v-else class="pulse-dot" />
      </div>
      <span class="step-label">{{ step.nodeName }}</span>
    </div>
  </div>
</template>

<style scoped>
.my-path-stepper {
  display: flex;
  align-items: flex-start;
  overflow-x: auto;
  padding: 0.5rem 0;
  gap: 0;
}
.step-circle.completed {
  background: var(--p-green-500);
  color: white;
}
.step-circle.current {
  border: 2px solid var(--p-blue-500);
  position: relative;
}
.pulse-dot {
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
</style>
```

### Example 5: Zod Validation Integration in ActionFormDialog
```typescript
// Source: Zod 4 docs (https://zod.dev/api, https://zod.dev/error-formatting)
import { z } from 'zod'

function validate(): boolean {
  const allFields = [...(props.sharedFields ?? []), ...(props.action?.formFields ?? [])]
  const schema = buildZodSchema(allFields)
  const result = schema.safeParse(formData.value)

  if (!result.success) {
    const flat = z.flattenError(result.error)
    const errs: Record<string, string> = {}
    for (const [fieldName, messages] of Object.entries(flat.fieldErrors)) {
      if (messages && messages.length > 0) {
        errs[fieldName] = messages[0]
      }
    }
    errors.value = errs
    return false
  }

  errors.value = {}
  return true
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Zod 3 `error.flatten()` | Zod 4 `z.flattenError(error)` | Zod 4.0 (2025) | Static function instead of method |
| Zod 3 `required_error` param | Zod 4 `error` callback param | Zod 4.0 (2025) | Unified error customization |
| PrimeVue 3 Steps | PrimeVue 4 Stepper (StepList/Step/StepPanel) | PrimeVue 4.0 (2024) | Irrelevant — user chose custom stepper |
| PrimeVue TabView | PrimeVue TabView (same API) | Still current | No migration needed |

**Deprecated/outdated:**
- Zod 3 `.flatten()` instance method: use `z.flattenError()` static function in Zod 4
- `required_error` / `invalid_type_error` params: deprecated in Zod 4, use `error` callback

## Open Questions

1. **Subtasks backend support**
   - What we know: User wants subtasks (checklist with checkboxes, progress, inline add) in v1 UI
   - What's unclear: The current Task entity has no `parentTaskId` or subtasks relationship. No backend CRUD for subtasks exists.
   - Recommendation: Build the `SubtasksList.vue` UI component with local state only (no persistence). Show as "beta" or connect to a simple subtasks array stored in task metadata (JSONB) if feasible. OR defer to a quick backend enhancement task within this phase.

2. **Time Tracking, Watchers backend support**
   - What we know: User wants sidebar cards for these in v1
   - What's unclear: No backend endpoints for time tracking (start/stop timer) or watchers (subscribe/unsubscribe)
   - Recommendation: Build display-only cards. Time Tracking shows `estimatedHours` from task. Watchers shows task creator avatar only. Both indicate "Full functionality coming soon" or simply omit interactive elements.

3. **Process Data Card — variable label resolution**
   - What we know: Variables are namespaced with node ID prefix. Labels come from form_schema field definitions.
   - What's unclear: When viewing a task, we only have the current task's form_schema. Variables from previous stages have different schemas.
   - Recommendation: Fetch history events, extract `VariablesMergedEvent` payloads. Use start form schema (already fetched in ProcessHistoryTimeline) + current task schema to build a label map. Unknown keys display with namespace stripped.

4. **Avatar component availability**
   - What we know: PrimeVue has Avatar and AvatarGroup components. They are NOT currently in `components.d.ts` auto-imports.
   - What's unclear: Whether auto-import resolver will pick them up automatically on first use, or manual registration needed.
   - Recommendation: They should auto-register (the resolver handles all PrimeVue components). Test on first use; if not, add to unplugin config.

## Backend API Surface for This Phase

All required backend endpoints already exist. Summary of endpoints the frontend will call:

| Endpoint | Method | Purpose | Status |
|----------|--------|---------|--------|
| `GET /organizations/{orgId}/tasks` | GET | List tasks (with workflow_summary) | Exists |
| `GET /organizations/{orgId}/tasks/{taskId}` | GET | Task detail (with workflow_context + form_schema) | Exists |
| `POST /organizations/{orgId}/tasks/{taskId}/complete` | POST | Submit action + formData | Exists (renamed from execute-action) |
| `POST /organizations/{orgId}/tasks/{taskId}/claim` | POST | Claim pool task | Exists |
| `POST /organizations/{orgId}/tasks/{taskId}/unclaim` | POST | Return task to pool | Exists |
| `GET /organizations/{orgId}/process-instances/{id}/history` | GET | Process events for stepper | Exists |
| `GET /organizations/{orgId}/process-instances/{id}/graph` | GET | Process graph for node names | Exists |
| `GET /organizations/{orgId}/process-instances/{id}` | GET | Instance details (tokens, variables) | Exists |

**No new backend work required for this phase.** All API endpoints are in place from Phases 1-3.

## Discretion Recommendations

### Action Buttons Placement: Sticky Header Bar (recommended)
**Rationale:** The Figma prototype shows action buttons at the top of the page, always visible. A sticky header bar ensures users can execute actions without scrolling. Pattern: fixed position bar with action buttons and status, visible on scroll.

### Activity/Tabs: Keep Existing Separate Tabs (recommended for v1)
**Rationale:** The existing tab structure (Comments, Attachments, Assignments, Labels, History) works and is already implemented. Rebuilding into a unified Activity Stream is significant effort for a cosmetic change. In v1, keep tabs but add filter chips within the History tab. The full unified Activity Stream from Figma can be a v2 enhancement.

### Empty States for Sidebar Cards
**Rationale:** Use PrimeVue's muted text style with a dash or "—" for empty values. For interactive cards without backend (Watchers, Time Tracking), show the card structure with placeholder content indicating the feature is display-only.

## Sources

### Primary (HIGH confidence)
- Codebase analysis: `TaskDetailContent.vue`, `ActionFormDialog.vue`, `DynamicFormField.vue`, `TaskCard.vue`, `task.api.ts`, `task.store.ts`, `task.types.ts` — direct code reading
- Backend `TaskController.php` — confirmed `/complete` endpoint name (line 115)
- Figma prototype screenshots `docs/design/img.png` through `img_6.png` — visual reference
- Zod 4 official docs (https://zod.dev/api) — schema building API
- Zod 4 error formatting (https://zod.dev/error-formatting) — `z.flattenError()` API

### Secondary (MEDIUM confidence)
- PrimeVue Stepper docs (https://primevue.org/stepper) — confirmed wizard-oriented, not suitable for read-only status display
- Zod 4 release notes (https://zod.dev/v4) — migration from v3
- PrimeVue auto-import resolver behavior — based on existing `components.d.ts` evidence

### Tertiary (LOW confidence)
- Subtasks implementation approach — no backend evidence, needs design decision
- Full Activity Stream complexity estimate — based on prototype analysis, no implementation reference

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all libraries already in project, versions confirmed from `package.json`
- Architecture: HIGH — detailed code analysis of existing components, clear refactoring path
- Pitfalls: HIGH — API mismatch verified from source code, Zod 4 API confirmed from official docs
- Stepper data derivation: MEDIUM — approach is sound but untested; depends on event data completeness
- Sidebar cards (v2 features): MEDIUM — UI-only approach clear, but scope of "display-only" vs "functional" needs planning decision

**Research date:** 2026-02-28
**Valid until:** 2026-03-28 (stable — all libraries are established, no major releases expected)
