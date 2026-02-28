# Feature Research

**Domain:** BPM Workflow-Task Integration
**Researched:** 2026-02-28
**Confidence:** MEDIUM-HIGH (Camunda official docs verified; Activiti/Appian/ProcessMaker from training data + cross-referenced with project spec TASK_ASSIGNMENT_SPEC.md which cites these sources)

---

## Research Context

The Procivo platform already ships: workflow designer, BPMN execution engine (token-based), task management (CRUD, kanban, comments, attachments, S3 files), and organizational structure (departments, roles, RBAC). This milestone integrates them. The feature landscape below is evaluated against what **mature BPM platforms** (Camunda 8, Activiti/Flowable, Appian, ProcessMaker, Bonita) provide specifically at the workflow-task integration layer.

---

## Feature Landscape

### Table Stakes (Users Expect These)

Features users assume exist in any BPM platform. Missing these = the product feels like a prototype, not a platform.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Task form rendering from schema | Every BPM since Activiti 5 renders forms on user tasks. Without it, workers have no structured input surface. | MEDIUM | Already have `DynamicFormField.vue` with text/number/date/select/checkbox/textarea/employee types. Need to wire schema from backend to component. |
| Action buttons derived from process definition | XOR gateway routing requires user to pick a path (Approve/Reject/etc.). Buttons encode the decision. Camunda, Appian, Flowable all do this. | MEDIUM | Architecture decision already made: forms per ACTION (transition), not per task. `action_key` already on TransitionDTO. |
| Form data merged into process variables | The submitted values must flow into the process instance so gateways can evaluate them. This is the core data contract of any BPM engine. | LOW | `ProcessInstance.variables` (JSONB) exists. `ExpressionEvaluator` exists. Need to wire `CompleteTaskNode` to merge submitted data. |
| XOR gateway condition evaluation | Without evaluated conditions, branching logic is inert — the entire point of process design is nullified. Camunda uses FEEL; Flowable uses UEL; Procivo uses Symfony ExpressionLanguage. | LOW | `ExpressionEvaluator` already integrated into `WorkflowEngine.handleExclusiveGateway()`. Backend already evaluates. Needs `condition_expression` to actually reference submitted variables. |
| Required field validation on completion | Users must not be able to submit incomplete forms. Standard contract in all BPM task completion APIs. | LOW | Frontend validation + backend re-validation in `CompleteTaskNode` handler. Backend is authoritative source for required validation. |
| Process context on task cards | Users must know which process a task belongs to and where in the process they are. Camunda Tasklist shows "process name" per task. Appian shows breadcrumb. | MEDIUM | `WorkflowTaskLink` bridges the modules. Need to expose `process_definition_name` + `node_name` in task list API. |
| Pool task claim mechanism | Enterprise standard since BPMN 2.0 `potentialOwner` / Camunda `candidateGroups`. Workers must be able to self-assign from a group queue. | MEDIUM | `ClaimTask`/`UnclaimTask` commands exist in codebase (untracked files visible in git status). `Task.claim()` / `Task.unclaim()` domain methods exist. Need API endpoints + frontend UI. |
| "Available" task filter tab | Workers need a dedicated view for claimable tasks. Camunda Tasklist, ProcessMaker, and Jira all have this concept. Without it, pool tasks are invisible. | LOW | Backend `ListTasks` query needs `available` filter (candidateRoleId/candidateDepartmentId matching current user). Frontend needs tab. |
| Task assignment from process designer | Process designers must configure who does each stage. Hardcoding assignees is not acceptable. All BPM platforms have assignment configuration in the modeler. | MEDIUM | `TaskNodeConfig.vue` exists (basic). Needs assignment strategy dropdown + conditional fields (role selector, dept selector, employee selector). |
| Basic form field types | Text, number, date, select (dropdown), checkbox, textarea — these are the minimum field type set present in every BPM form engine. | LOW | Already implemented in `DynamicFormField.vue` and `FormFieldType` in types. |
| Form validation feedback in UI | Inline error messages per field on validation failure. Standard UX contract. | LOW | `DynamicFormField.vue` has `error` prop and `<small v-if="error" class="p-error">`. Just needs error state propagation from store. |
| Single-action task completion | Linear tasks with one outgoing transition must show a single "Complete" button (not multiple action buttons). Camunda, Flowable handle this automatically. | LOW | `WorkflowEngine.executeAction()` already handles "if 1 outgoing transition and no actionKey — use it". Frontend must detect `actions.length === 1` and render accordingly. |
| Assignee display on tasks | Show who is assigned or "Unassigned". If pool task — show candidate group name. Basic human-readable status. | LOW | Task entity has `assigneeId`, `candidateRoleId`, `candidateDepartmentId`. DTO needs to expose these. |
| Start process from tasks page | Users want to start a process from a task management context without navigating to the workflow designer. All major BPM platforms have process start in the task portal. | LOW | "Create" button dropdown: "Standalone task" / "Start process". Needs process definition list + start process API call. |

---

### Differentiators (Competitive Advantage)

Features that set Procivo apart. Not required to call the product a BPM platform, but add meaningful value.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Per-action dynamic form fields | Each action button (Approve, Reject) reveals different form fields specific to that decision. Camunda Forms doesn't support this natively — forms are static per task. Procivo's model (form_schema.actions with per-action fields) is more flexible. | MEDIUM | Architecture already decided. Backend must build `form_schema.actions[]` from outgoing transitions' `form_fields`. Frontend: clicking action expands action-specific fields before confirming. |
| Process history timeline on task detail | Showing the audit trail of process execution (which nodes completed, who did what, when) inside the task detail. Appian has this; Camunda Tasklist doesn't by default. Adds operational transparency. | MEDIUM | Requires querying `ProcessInstance` event store and WorkflowTaskLinks. New `ProcessHistoryTimeline.vue` component (untracked in git status already). |
| Conditional field visibility (field dependencies) | Fields that show/hide based on other field values. E.g., "specify reason" appears only if "Rejected" is selected. Camunda form-js supports this via FEEL expressions. | HIGH | `FormFieldDefinition` currently has: name, label, type, required, options. Would need `visibleIf: string` expression field. Likely v1.x not v1. |
| Process graph monitoring view | Visual process graph with active token positions and completion status. Useful for managers and process owners to see where things are stuck. ProcessMaker and Camunda Operate have this. | HIGH | `ProcessMonitorGraph.vue` exists as untracked file. Backend query `GetProcessInstanceGraph` exists as untracked directory. Candidate for this milestone or next. |
| Previous performer assignment strategy | Automatically assign a task to whoever completed a specific prior stage. Useful for approval chains ("return to original submitter"). Camunda supports this via expressions; it's not common in simpler BPM tools. | HIGH | Requires querying `WorkflowTaskLink` history to find who completed a node. Phase 2 in `TASK_ASSIGNMENT_SPEC.md`. Defer from current milestone. |
| By Manager assignment strategy | Route task to the manager of the process initiator or previous performer. Handles escalation patterns automatically. | HIGH | Requires org hierarchy traversal. Phase 2 in spec. Defer. |
| Next assignee selector in action form | Allow task completer to nominate who should receive the next task, overriding the default assignment. Useful for "assign to me" or ad-hoc delegation. | MEDIUM | Referenced in `PROJECT.md` `NextAssignmentConfig`. Requires `_assignee_for_{nodeId}` variable convention (already in `OnTaskNodeActivated` as `from_variable` strategy). Adds powerful human-in-the-loop flexibility. |
| Draft variable saving | Save form progress without completing the task. Camunda Tasklist API has `POST /v1/tasks/{taskId}/variables` for draft variables. Prevents data loss on long-form tasks. | MEDIUM | Not in current scope. Requires separate draft store in Task metadata or local browser storage. Browser-side draft is simpler: localStorage keyed by taskId. Defer to v1.x. |

---

### Anti-Features (Commonly Requested, Often Problematic)

Features to explicitly NOT build in this milestone.

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Live process migration (migrate running instances to new version) | Process definitions evolve; teams want running instances to use new logic. | Requires versioned snapshot of process graph at instance creation time, migration rules per node, data mapping between versions. Enterprise-grade complexity. Camunda calls this a separate concern (process migration API). | New instances use new versions. Existing instances complete on the version they started. Already explicit in PROJECT.md out-of-scope. |
| Parallel gateway full UI implementation | AND-split creates parallel tasks visible to multiple users simultaneously. Seems like obvious BPM feature. | Requires multi-token task visibility rules (which parallel tasks should each user see?), AND-join synchronization in UI. Complex UX edge cases. Core engine already handles the token mechanics. | XOR gateway is the priority for real decision-making. Parallel tasks are created but completion is independent. Defer UI complexity. PROJECT.md already defers this. |
| Rich text form field type | Users want formatting in text fields (bold, bullets). | Adds a WYSIWYG editor dependency, sanitization requirements, and storage complexity (store HTML? Markdown?). Camunda Forms has a "text view" component but not rich-text input. | Plain textarea suffices for any BPM use case in this milestone. Defer rich text to v2. |
| File upload form field type | Collect documents as part of task completion. Seems natural for doc approval workflows. | File upload in forms requires separate file storage API, progress indicators, file validation (size, type), and linking uploaded files to process variables (store a URL? a file ID?). S3FileStorage exists but wiring to dynamic form fields is complex. | Task already supports attachments via the existing comment+attachment system. Process variables can store attachment IDs. Defer file-in-form to v1.x if needed. |
| Real-time task list updates (SSE/WebSocket) | Users want to see new tasks appear without refreshing when process moves. | Mercure is available in the stack, but wiring process-engine events through Mercure to task list requires event publishing from `OnTaskNodeActivated`, hub authentication, and client-side subscription. | Poll-based refresh (refetch on action complete) is simpler and sufficient. Mercure integration is a separate milestone feature. |
| Drag-and-drop kanban for workflow tasks | Users familiar with Jira want to drag tasks between columns to complete them. | Workflow tasks are completed via action forms (structured decision + data). Dragging to a column bypasses form submission, condition evaluation, and variable merging. | Keep drag-and-drop for standalone tasks. Workflow tasks must use the action form flow. Workflow and non-workflow task rendering can differ. |
| Expression builder UI for gateway conditions | Non-technical users want a visual condition builder ("if amount > 1000"). | A condition builder for Symfony ExpressionLanguage requires parsing, AST representation, UI for operators/values, and testing against sample variables. Full scope project on its own. | Text input with expression language reference. Process designers are technical users. YAGNI for a learning/interview project. |
| SLA / deadline escalation | Auto-reassign or escalate tasks overdue. | Requires scheduled job (cron or Symfony Scheduler), escalation rules per task node, notification chains, and override logic. Phase 3 in `TASK_ASSIGNMENT_SPEC.md`. | Show due date on task. Warning styling for overdue. Actual escalation deferred. |

---

## Feature Dependencies

```
[XOR Gateway Condition Evaluation]
    └──requires──> [Form data merged into process variables]
                       └──requires──> [CompleteTaskNode accepts action + formData]
                                          └──requires──> [form_schema stored in Task metadata]

[Per-action dynamic form fields in UI]
    └──requires──> [form_schema.actions[] built by OnTaskNodeActivated]
                       └──requires──> [Transitions have form_fields (JSONB column exists)]

[Pool task claim button in UI]
    └──requires──> [ClaimTask API endpoint]
                       └──requires──> [ClaimTask command + handler]
                                          └──requires──> [Task.claim() domain method — DONE]

[Available task filter in UI]
    └──requires──> [ListTasks query supports 'available' filter]
                       └──requires──> [Task has candidateRoleId / candidateDepartmentId — DONE]

[Start Process button in Tasks page]
    └──requires──> [Process definitions list API — DONE]
                       └──requires──> [Start Process Instance API — DONE]

[Process context on task cards]
    └──requires──> [BatchTaskWorkflowSummary query (untracked file exists)]
                       └──requires──> [WorkflowTaskLink joined with ProcessInstance + ProcessDefinition]

[Process history timeline on task detail]
    └──requires──> [GetProcessInstanceGraph query (untracked dir exists)]
                       └──requires──> [ProcessInstance event store readable]

[Assignment strategy in designer UI]
    └──requires──> [Role/Dept/Employee selectors in TaskNodeConfig.vue]
                       └──requires──> [Organization API (roles, departments, employees) — DONE]

[Per-action dynamic fields] ──enhances──> [XOR gateway condition evaluation]
  (fields submitted per action become the variables gateways evaluate)

[Pool task claim] ──conflicts──> [Direct drag-to-complete in kanban]
  (workflow tasks must go through action form; kanban drag bypasses it)
```

### Dependency Notes

- **form_schema in Task metadata requires transitions have form_fields:** The `TransitionDTO.form_fields` field already exists in the TypeScript types and backend transition entity. This is the key prerequisite for per-action forms.
- **XOR condition evaluation requires variables in process instance:** `WorkflowEngine.handleExclusiveGateway()` already reads `instance.variables()` and calls `expressionEvaluator.evaluate()`. The missing piece is populating those variables from submitted form data.
- **Claim mechanism requires pool task identification:** `Task.isPoolTask()` checks `candidateRoleId || candidateDepartmentId`. Assignment strategies `by_role` and `by_department` must set these fields at task creation (via `OnTaskNodeActivated` → `AssignmentResolver`).
- **Next assignee selector (differentiator) requires `from_variable` strategy:** `OnTaskNodeActivated` already implements `from_variable` strategy reading `_assignee_for_{nodeId}` from variables. The action form just needs to optionally write this variable on submission.

---

## MVP Definition

### Launch With (v1 — This Milestone)

The minimum feature set to call the workflow-task integration "working end-to-end."

- [ ] **form_schema built from task node config + outgoing transitions** — Without this, forms cannot render. This is the structural foundation everything else depends on.
- [ ] **Task detail page renders dynamic form with action buttons** — The user-facing surface. Workers must be able to see and interact with the form.
- [ ] **CompleteTaskNode API accepts `{ action, formData }`** — The completion contract. Required fields validated server-side.
- [ ] **Form data merged into ProcessInstance.variables** — Enables gateway conditions. Core data flow.
- [ ] **XOR gateway evaluates conditions against process variables** — Validates the entire process branching concept works. Already mostly wired; just needs submitted data to flow through.
- [ ] **Assignment strategies resolved at task creation (unassigned, specific_user, by_role, by_department)** — Four strategies cover 90% of real-world cases. `AssignmentStrategy` enum has 4 cases; `OnTaskNodeActivated` has resolution logic.
- [ ] **ClaimTask / UnclaimTask API endpoints** — Pool tasks need the claim mechanism to be usable.
- [ ] **Pool task banner in task detail (claim/assign to me button)** — The visible UI for pool tasks.
- [ ] **Available filter tab in task list** — Workers must find claimable tasks.
- [ ] **Process context badge on task cards** — Shows process name and stage. Makes workflow tasks distinguishable from standalone tasks.
- [ ] **Assignment strategy configuration in Workflow Designer (TaskNodeConfig.vue)** — Process designers must configure assignment without editing JSON.
- [ ] **Start Process from Tasks page** — Closes the loop: users can trigger processes from the task context, not just from the workflow designer.

### Add After Validation (v1.x)

Features to add once the core integration is proven working.

- [ ] **Process history timeline on task detail** — Adds transparency; not needed for basic task completion. Files already started (`ProcessHistoryTimeline.vue`).
- [ ] **Process graph monitoring view** — For process owners. Files already started (`ProcessMonitorGraph.vue`, `GetProcessInstanceGraph` query).
- [ ] **Draft variable saving (browser localStorage)** — UX improvement for long forms. Low effort with browser storage.
- [ ] **Conditional field visibility (`visibleIf` expression)** — Makes forms smarter. Requires `FormFieldDefinition` type extension.
- [ ] **Next assignee selector in action form** — Ad-hoc delegation. `from_variable` strategy already handles the runtime side.

### Future Consideration (v2+)

Features requiring separate planning.

- [ ] **Previous performer and By Manager assignment strategies** — Requires org hierarchy traversal and process history queries. Phase 2 in `TASK_ASSIGNMENT_SPEC.md`.
- [ ] **SLA / deadline escalation** — Requires Symfony Scheduler integration and escalation rule config. Phase 3 in spec.
- [ ] **Substitution / authority transfer** — When employees go on leave. Phase 5 in spec. Touches every module.
- [ ] **File upload form field type** — Wiring S3 storage into dynamic forms. Requires dedicated design.
- [ ] **Real-time task list updates via Mercure** — Performance/UX improvement. Mercure hub available; integration is separate scope.
- [ ] **Smart assignment (round-robin, workload-balanced, AI)** — Phase 4 in spec. Requires workload data and assignment analytics.

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| form_schema built from node + transitions | HIGH | MEDIUM | P1 |
| Task detail page with form + actions | HIGH | MEDIUM | P1 |
| CompleteTaskNode with action + formData | HIGH | LOW | P1 |
| Form data → process variables | HIGH | LOW | P1 |
| XOR gateway conditions using variables | HIGH | LOW | P1 |
| Assignment strategies at task creation | HIGH | MEDIUM | P1 |
| ClaimTask / UnclaimTask API | HIGH | LOW | P1 |
| Pool task banner in UI | HIGH | LOW | P1 |
| Available filter tab | HIGH | LOW | P1 |
| Assignment config in designer | MEDIUM | MEDIUM | P1 |
| Process context on task cards | MEDIUM | LOW | P1 |
| Start Process from Tasks page | MEDIUM | LOW | P1 |
| Process history timeline | MEDIUM | MEDIUM | P2 |
| Process graph monitoring view | MEDIUM | HIGH | P2 |
| Conditional field visibility | MEDIUM | MEDIUM | P2 |
| Next assignee selector | LOW | LOW | P2 |
| Draft variable saving | LOW | LOW | P2 |
| Previous performer strategy | MEDIUM | HIGH | P3 |
| By Manager strategy | MEDIUM | HIGH | P3 |
| SLA escalation | LOW | HIGH | P3 |

**Priority key:**
- P1: Must have for this milestone launch
- P2: Should have, add when P1 is complete
- P3: Future milestone consideration

---

## Competitor Feature Analysis

| Feature | Camunda 8 | Flowable | Procivo Plan |
|---------|-----------|---------|--------------|
| Form types | Text, Number, Date, Select, Checkbox, Textarea, File, User picker (via identity service), Tag list, DateTime, Table | Text, Number, Date, Enum (select), Custom renderers | text, number, date, select, checkbox, textarea, employee — covers 80% of use cases without file/table complexity |
| Form scope | One form per task (static; not per-action) | One form per task | Per-action forms: different fields per action button. More flexible than Camunda baseline. |
| Assignment | assignee (direct), candidateGroups, candidateUsers — all can be expressions | humanPerformer (direct), potentialOwner (groups) | unassigned, specific_user, by_role, by_department — covers BPMN 2.0 humanPerformer + potentialOwner |
| Claim | `PATCH /v1/tasks/{taskId}/assign` to self | Task claim + release APIs | POST claim, POST unclaim — same pattern |
| Gateway conditions | FEEL expressions (e.g., `= amount > 1000`) | UEL expressions (e.g., `${amount > 1000}`) | Symfony ExpressionLanguage (e.g., `amount > 1000`) — functionally equivalent |
| Variables | Input/output mappings; task variables scoped to task + process | Process variables accessible everywhere | Process-wide JSONB variables; all form data merged at task completion |
| Process context in task UI | Task shows process name + diagram view | Task shows process info | Process name badge + stage indicator on task cards |
| Escalation | Not built-in (use Timer Boundary Events) | Timer Boundary Events | Phase 3 (deferred) |
| Substitution | No built-in; identity service plugins | No built-in | Phase 5 (deferred) |
| Draft saving | `POST /v1/tasks/{taskId}/variables` for draft | Not native | Browser localStorage (v1.x) |

---

## Confidence Assessment

| Claim | Confidence | Source |
|-------|-----------|--------|
| Camunda Forms: one form per task (not per-action) | HIGH | Official Camunda 8 docs (user-tasks page) — forms linked by form ID |
| Camunda: assignee, candidateGroups, candidateUsers | HIGH | Official Camunda 8 docs (user-tasks page) — verified 2026-02 |
| Camunda: claim via PATCH assign to self | HIGH | Official Tasklist API docs — 7 endpoints listed with descriptions |
| Camunda: FEEL for gateway conditions | HIGH | Official exclusive-gateways docs — examples shown with FEEL syntax |
| Camunda form field types (text, number, date, select, checkbox, textarea, file) | MEDIUM | form-js GitHub README + Camunda docs general reference; specific types confirmed via form-js library description |
| Flowable: UEL expressions for conditions | MEDIUM | Training data cross-referenced with TASK_ASSIGNMENT_SPEC.md competitor table |
| Appian: auto-accept if pool has 1 person | MEDIUM | TASK_ASSIGNMENT_SPEC.md (cites Appian) — not independently verified via official docs |
| ProcessMaker: self-service task claiming | MEDIUM | TASK_ASSIGNMENT_SPEC.md (cites ProcessMaker) — training data confirms this is standard |

---

## Sources

- Camunda 8 User Tasks documentation: https://docs.camunda.io/docs/components/modeler/bpmn/user-tasks/
- Camunda Exclusive Gateways documentation: https://docs.camunda.io/docs/components/modeler/bpmn/exclusive-gateways/
- Camunda Tasklist user guide: https://docs.camunda.io/docs/components/tasklist/userguide/using-tasklist/
- Camunda Tasklist REST API — Task Controller: https://docs.camunda.io/docs/apis-tools/tasklist-api-rest/controllers/tasklist-api-rest-task-controller/
- Camunda Forms — Button element: https://docs.camunda.io/docs/components/modeler/forms/form-element-library/forms-element-library-button/
- Procivo TASK_ASSIGNMENT_SPEC.md — competitor patterns (Camunda, Jira, Appian, Bitrix24, ProcessMaker, Bonita, BPMN 2.0 spec)
- Procivo WORKFLOW_TASKS_INTEGRATION_PLAN.md — architectural decisions and data flow
- Procivo codebase — ExpressionEvaluator.php, WorkflowEngine.php, Task.php, DynamicFormField.vue, AssignmentStrategy.php

---

*Feature research for: BPM Workflow-Task Integration (Procivo)*
*Researched: 2026-02-28*
