# Project Research Summary

**Project:** Procivo — BPM Workflow-Task Integration Milestone
**Domain:** Business Process Management (BPM) — workflow engine integration with human task management
**Researched:** 2026-02-28
**Confidence:** HIGH

## Executive Summary

This milestone integrates two existing modules — the Workflow Engine (token-based BPMN execution) and the TaskManager (CRUD tasks, kanban, comments, files) — into a unified BPM platform where process execution drives task creation, dynamic form rendering, and structured decision capture. The foundational architecture is already well-established: the token model, `WorkflowTaskLink` bridge, `ExpressionEvaluator`, `AssignmentResolver`, and `DynamicFormField.vue` all exist. The work is wiring these components together along the critical data path: process designer configures forms per action → task node activation embeds the form schema → user submits form data through an action dialog → backend validates and merges into process variables → XOR gateway evaluates conditions and routes the token. Every piece exists; none are connected end-to-end.

The recommended approach is strict bottom-up implementation following the architectural layer dependency chain: database migrations first, domain entity updates second, application services third, command/event handlers fourth, API endpoints fifth, and frontend last. This order is non-negotiable — building the frontend form dialog before the backend stores form_schema in the task entity will produce dead-end work. Two architectural decisions are already locked in and must not be reversed: (1) form schema is snapshotted onto each Task at creation time, never fetched live from the process definition; (2) submitted form data is namespaced by node ID when merged into process variables to prevent collision across stages. These decisions directly prevent the two highest-cost pitfalls identified in research.

The key risks are all implementation-time correctness problems, not architectural unknowns. The most dangerous is silent wrong-path routing when an XOR gateway condition references a variable that is absent from process variables — Symfony ExpressionLanguage resolves undefined names as null rather than throwing, causing the gateway to always take the rejection path without any error. Close behind is the race condition on pool task claim, which requires a pessimistic database lock (`LockMode::PESSIMISTIC_WRITE`) to prevent double-assignment. Both must be addressed in the first implementation session, before any frontend work begins.

---

## Key Findings

### Recommended Stack

No new packages are required. The entire milestone can be built using libraries already installed: `symfony/expression-language`, `symfony/validator`, `symfony/messenger`, Doctrine ORM with JSONB, PrimeVue 4, Zod 4, and Pinia 3. The stack is mature, verified against official Symfony 8 and Vue 3 documentation, and already proven in the codebase.

**Core technologies:**
- `symfony/expression-language 8.0.*`: Gateway condition evaluation — sandboxed, supports all needed operators (`==`, `in`, `and/or`, `matches`, null-coalescing). Already integrated in `ExpressionEvaluator`. No custom parser needed.
- `symfony/validator 8.0.*` + `Assert\Collection`: Dynamic backend form validation — build constraint collection programmatically from `FormFieldDefinition[]` schema. Replaces the current minimal required-only check.
- Doctrine ORM `json` type (PostgreSQL JSONB): Stores `form_schema`, `variables`, `config` on task and process entities — already in use across the codebase. Zero additional setup.
- `zod 4.3.6`: Frontend dynamic schema building — `buildZodSchema(fields: FormFieldDefinition[])` produces type-safe validation before API submission. `safeParse()` returns field-level errors consumable by `DynamicFormField.vue`.
- `symfony/messenger 8.0.*` (3-bus CQRS): Routing pattern already established — commands to `command.bus`, queries to `query.bus`, domain events to `event.bus` (async via RabbitMQ).

See `.planning/research/STACK.md` for code patterns for each integration.

### Expected Features

Research against Camunda 8, Flowable, Appian, ProcessMaker, and Bonita confirms the feature set. The distinction between table stakes (expected in any BPM platform) and differentiators (where Procivo exceeds the baseline) is clear.

**Must have (table stakes) — v1:**
- Dynamic form rendering on task detail, with fields derived from process node configuration
- Per-action form fields: different fields shown based on which decision button is clicked (Approve/Reject/etc.)
- Backend form data validation on task completion — not just frontend; backend is the authority
- Form data merged into process variables so XOR gateways can evaluate conditions
- XOR gateway condition evaluation against submitted variables (end-to-end, not just engine logic in isolation)
- Pool task claim/unclaim mechanism with candidate role/department validation
- "Available" task filter tab showing claimable pool tasks scoped to the current user's roles
- Assignment strategy configuration in the Workflow Designer (TaskNodeConfig.vue) — unassigned, specific user, by role, by department
- Process context badge on task cards (process name + stage name)
- Start process from the Tasks page without navigating to the workflow designer

**Should have (differentiators) — v1.x:**
- Process history timeline on task detail (audit trail of node completions)
- Process graph monitoring view with active token positions (files already started)
- Conditional field visibility (`visibleIf` expression on `FormFieldDefinition`)
- Next assignee selector in action form (ad-hoc delegation via `_assignee_for_{nodeId}` variable)
- Draft variable saving via browser localStorage

**Defer (v2+):**
- Previous performer and By Manager assignment strategies (requires org hierarchy traversal)
- SLA / deadline escalation (requires Symfony Scheduler integration)
- File upload form field type (requires S3-to-dynamic-form wiring design)
- Real-time task list updates via Mercure (separate milestone)
- Substitution / authority transfer (touches all modules)

See `.planning/research/FEATURES.md` for full competitor analysis and dependency graph.

### Architecture Approach

The architecture follows Clean Architecture strictly: domain services (`WorkflowEngine`, `ExpressionEvaluator`) are pure PHP with no I/O; application handlers coordinate domain services and I/O-bound operations; the `WorkflowTaskLink` bridge entity in the Workflow module is the only coupling point between modules; `OrganizationQueryPort` (interface in TaskManager's Application/Port) provides an anti-corruption layer for org data access. The most important constraint: the `WorkflowEngine` must never be called from the Presentation layer — all task completion flows through `ExecuteTaskActionCommand` on the command bus.

**Major components:**
1. `WorkflowEngine` (Workflow/Domain) — token lifecycle, gateway evaluation, process advancement; pure logic with no I/O
2. `ProcessInstance` (Workflow/Domain) — event-sourced aggregate; holds tokens + JSONB variables; reconstituted from EventStore
3. `WorkflowTaskLink` (Workflow/Domain) — bridge entity mapping `(processInstanceId, tokenId)` to `taskId`; token-scoped, not process-scoped
4. `OnTaskNodeActivated` (Workflow/Application) — listens on async event bus; builds `form_schema` from node config + transitions; resolves assignment strategy; dispatches `CreateTaskCommand`
5. `ExecuteTaskActionHandler` (Workflow/Application) — validates submitted form data, merges into process variables, calls `WorkflowEngine.executeAction()`
6. `AssignmentResolver` (TaskManager/Application) — resolves assignment strategy to `AssignmentResult`; uses `OrganizationQueryPort`
7. `Task` (TaskManager/Domain) — stores snapshotted `form_schema` as JSONB; holds `candidateRoleId` / `candidateDepartmentId` for pool tasks

See `.planning/research/ARCHITECTURE.md` for complete data flows and build-order dependency graph.

### Critical Pitfalls

1. **Form schema as live reference instead of snapshot** — Task detail queries process definition on every open. Fix: snapshot full `form_schema` into `Task` at creation time in `OnTaskNodeActivated`. Never re-read from process definition. Existing tasks show the schema they were born with, even after definition updates. Address in Session 2.

2. **Variable key collisions across stages** — Two task nodes with a field named `comment` silently overwrite each other in `ProcessInstance.variables`. Fix: namespace all merged variables by node ID at merge time (e.g., `review_docs.comment`). Establish this convention in Session 1 before any form data flows. Recovery cost is HIGH.

3. **ExpressionEvaluator silently routing to wrong path** — Undefined variable in condition expression resolves to `null` (no exception); XOR gateway silently takes rejection path for all instances. Fix: (a) log a structured warning when expression references absent variable; (b) validate condition expressions against known field namespaces at process definition publish time. Address in Session 1.

4. **Race condition on pool task claim** — Two users claim the same task simultaneously; both succeed; task has two effective assignees. Fix: pessimistic lock (`LockMode::PESSIMISTIC_WRITE`) in `ClaimTaskHandler`. Address in the claim/unclaim handler session.

5. **Token-task link broken by loops and parallel branches** — `findLatestByProcessInstanceId` reuses the same link when a process visits a task node twice (rejection loop). Fix: change all link resolution to token-scoped (`findByProcessInstanceIdAndTokenId`). One link per token activation. Address in Session 2.

6. **Frontend-only form validation** — Backend completes the task with empty `formData`, corrupting process variables and leaving the process instance in an unrecoverable state. Fix: `ExecuteTaskActionHandler` reads the Task's `form_schema` and validates submitted data server-side using `Assert\Collection`. Address in Session 3.

See `.planning/research/PITFALLS.md` for security and UX pitfalls, integration gotchas, and recovery strategies.

---

## Implications for Roadmap

The research reveals a clean, dependency-ordered 5-session breakdown. The critical insight is that all of the hard backend wiring must precede any frontend polish. The session structure below follows the layered build order in ARCHITECTURE.md.

### Session 1: Backend Foundation — Variables, Gateways, and Validation Architecture

**Rationale:** The entire milestone depends on process variables flowing correctly from form submission through to gateway evaluation. If variable namespacing is established incorrectly here, the recovery cost is HIGH (affects all running instances). The `ExpressionEvaluator` silent-error pitfall and variable key collision pitfall must both be solved before any form data exists in the system.

**Delivers:**
- Namespaced variable merge strategy established in `ProcessInstance.mergeVariables(nodeId, actionKey, formData)`
- `ExpressionEvaluator` enhanced with undefined-variable warning and validation at publish time
- `Assert\Collection`-based backend validation foundation in `FormSchemaValidator` (pure domain service)
- All database migrations applied: `ProcessInstance.variables`, `Transition.form_fields`, `Task.candidate_role_id`, `Task.candidate_department_id`, `Task.form_schema`

**Addresses from FEATURES.md:** XOR gateway condition evaluation, required field backend validation
**Avoids from PITFALLS.md:** Variable key collisions (Pitfall 2), ExpressionEvaluator silent errors (Pitfall 3)
**Research flag:** Standard patterns — well-documented Symfony APIs, no additional research needed

---

### Session 2: Task Form Schema — Creation-Time Embedding and Pool Task Infrastructure

**Rationale:** With migrations applied and the merge strategy established, `OnTaskNodeActivated` can now build and persist the full `form_schema` snapshot. Token-scoped link resolution must also be fixed here, because the form schema embedding and the link strategy are both part of the task creation path in the same handler.

**Delivers:**
- `OnTaskNodeActivated` updated: builds `form_schema` from node config + outgoing transition fields, stores in `Task.form_schema` JSONB at creation time
- Token-scoped link resolution: `findByProcessInstanceIdAndTokenId` replaces `findLatestByProcessInstanceId`
- `AssignmentResolver` wired into `OnTaskNodeActivated` — all 4 strategies produce `candidateRoleId` / `candidateDepartmentId` correctly
- `OrganizationQueryPort` interface + `DoctrineOrganizationQueryAdapter` implementation
- `GetTaskWorkflowContextHandler` query returns `TaskWorkflowContextDTO` (process name, stage name, form_schema)

**Addresses from FEATURES.md:** Form schema structure, process context on tasks, assignment strategy resolution
**Avoids from PITFALLS.md:** Form schema snapshot vs live reference (Pitfall 1), token-task link breaks on loops (Pitfall 5)
**Research flag:** Standard patterns — direct codebase integration, no external research needed

---

### Session 3: Task Completion API and Claim/Unclaim

**Rationale:** With form_schema embedded in tasks and the workflow context query working, the completion and claim endpoints can be implemented. Backend validation must be added here using the `FormSchemaValidator` from Session 1 — the API must be hardened before the frontend calls it.

**Delivers:**
- `POST /tasks/{id}/complete` — `ExecuteTaskActionHandler` validates form data against `Task.form_schema`, merges into process variables, advances token
- `POST /tasks/{id}/claim` — pessimistic lock, candidate pool membership validation, `Task.claim()` domain event
- `POST /tasks/{id}/unclaim` — `Task.unclaim()` domain event
- `GET /tasks/{id}/workflow-context` API endpoint
- `ListTasks` query updated with `available` filter (pool tasks matching current user's roles/departments)
- RBAC / org boundary checks on complete and claim handlers

**Addresses from FEATURES.md:** Task completion with action + form data, pool task claim, available task filter
**Avoids from PITFALLS.md:** Frontend-only form validation (Pitfall 6), race condition on claim (Pitfall 4), org boundary security mistake
**Research flag:** Standard patterns — Doctrine pessimistic lock is well-documented; no novel architecture

---

### Session 4: Frontend Task Integration

**Rationale:** All backend APIs are now functional. Frontend can be built against real endpoints. The task detail layout, action form dialog, and pool task UI are all unblocked.

**Delivers:**
- `TaskDetailContent.vue` renders process badge, shared fields always visible, action buttons derived from `form_schema.actions`
- `ActionFormDialog.vue` polished — action-specific fields, Zod dynamic schema validation, field-level error display
- Pool task banner in task detail: "Assign to Me" / "Unclaim" button with candidate context
- `TaskListPanel` updated with "Available" tab and process context badge on `TaskCard`
- "Start Process" button in TasksPage (dropdown: standalone task vs. start process)
- Collapse single-action task to one "Complete" button without dialog (when `actions.length === 1` and no action-specific fields)

**Addresses from FEATURES.md:** Task detail page, pool task UI, process context badge, start process from tasks
**Avoids from PITFALLS.md:** UX pitfalls — shared field validation before action buttons, no drag-to-complete for workflow tasks
**Research flag:** Standard patterns — PrimeVue 4 and Zod patterns already documented in STACK.md

---

### Session 5: Workflow Designer — Assignment Configuration and Transition Forms

**Rationale:** Without designer configuration, process designers must edit JSON directly to set assignment strategies and per-action form fields. This session closes the full loop: designer configures → process runs → task created → user completes → process advances.

**Delivers:**
- `TaskNodeConfig.vue` updated: assignment strategy dropdown (unassigned, specific user, by role, by department) with conditional selectors for role/dept/employee
- Transition property panel: form fields builder per transition (`TransitionPropertyPanel` or within `NodePropertyPanel`)
- `StartNodeConfig.vue` review: ensure process initiator variable (`_task_creator_id`) is injected into initial variables at start
- End-to-end smoke test: design a 3-stage approval process in the designer, start it, complete each stage with different actions, verify XOR routing works

**Addresses from FEATURES.md:** Assignment strategy configuration in designer, per-action form fields in designer
**Avoids from PITFALLS.md:** Undefined variables from mis-configured conditions caught by publish-time validation (Pitfall 3)
**Research flag:** Standard patterns — organization APIs already exist; `vue-flow` node config pattern already established

---

### Phase Ordering Rationale

- Session 1 before everything else because variable namespacing is a foundational data contract. Changing it after form data exists requires a data migration on production records.
- Session 2 before Session 3 because the completion handler reads `Task.form_schema` — that column must be populated before the endpoint can validate data.
- Session 3 before Session 4 because frontend forms must call real endpoints during development to catch integration bugs. Mock data hides contract mismatches.
- Session 4 before Session 5 because the designer improvements (Session 5) produce process definitions that run through the full Session 1-4 stack. Integration smoke testing requires Sessions 1-4 to be complete first.
- This order directly mirrors the architectural layer dependency chain documented in ARCHITECTURE.md (Layer 1 → 6).

### Research Flags

Phases likely needing deeper research during planning:
- **None identified.** All sessions use proven patterns from the existing codebase or verified official documentation. The ARCHITECTURE.md contains production-quality code examples for every novel integration.

Phases with standard patterns (research-phase can be skipped):
- **All 5 sessions:** Stack is fixed, patterns are documented, architecture is established. The research files provide sufficient implementation guidance without additional /gsd:research-phase calls.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All libraries already installed and verified. No new dependencies. Code patterns verified against official Symfony 8 and Vue 3 docs. |
| Features | HIGH | Core table stakes verified against Camunda 8 official docs. Differentiator features cross-referenced with existing project specs (TASK_ASSIGNMENT_SPEC.md, WORKFLOW_TASKS_INTEGRATION_PLAN.md). |
| Architecture | HIGH | Based on direct codebase analysis of the existing Procivo modules. All component boundaries and data flows derived from actual code, not inference. |
| Pitfalls | HIGH | 5 of 6 critical pitfalls identified from direct code inspection (ExpressionEvaluator catch pattern, findLatestByProcessInstanceId usage, absence of pessimistic lock, etc.). One pitfall (frontend-only validation) from standard BPM security analysis. |

**Overall confidence: HIGH**

### Gaps to Address

- **Expression publish-time validation scope:** Research recommends validating condition expressions against the variable namespace produced by upstream task nodes at process definition publish time. The exact query to reconstruct the variable namespace from the process graph (which nodes produce which variable keys) needs to be designed during Session 1 implementation.
- **`AssignmentResolver` organization-scope queries:** All `by_role` and `by_department` queries must be scoped to `organizationId`. The `OrganizationQueryPort` interface definition must include `orgId` on all methods. Verify the existing `DoctrineOrganizationQueryAdapter` (untracked file) includes this scoping before Session 2.
- **Parallel gateway task visibility:** Parallel branches create multiple simultaneous tasks. FEATURES.md explicitly defers the UX complexity, but the backend token-scoped link fix in Session 2 must handle the parallel case correctly (two `TaskNodeActivatedEvents` with different tokenIds for the same process instance). Add a targeted integration test.
- **Zod 4.3.6 import compatibility:** Zod 4.x changed some import patterns from Zod 3.x. Verify that the existing `import { z } from 'zod'` syntax used in the codebase is compatible with the installed 4.3.6 version before building the dynamic schema builder in Session 4.

---

## Sources

### Primary (HIGH confidence)
- Official Symfony 8.0 ExpressionLanguage docs — https://symfony.com/doc/current/components/expression_language.html — operators, syntax, custom providers
- Official Symfony 8.0 Validator docs — https://symfony.com/doc/current/validation.html — `Assert\Collection` programmatic pattern
- Official Symfony 8.0 Messenger docs — https://symfony.com/doc/current/messenger.html — multi-bus CQRS configuration
- Official Camunda 8 User Tasks docs — https://docs.camunda.io/docs/components/modeler/bpmn/user-tasks/ — assignment model, claim API
- Official Camunda 8 Exclusive Gateways docs — https://docs.camunda.io/docs/components/modeler/bpmn/exclusive-gateways/ — FEEL expressions as reference point
- Procivo codebase direct analysis — `/Users/leleka/Projects/procivo/backend/` and `/Users/leleka/Projects/procivo/frontend/` — all existing implementations

### Secondary (MEDIUM confidence)
- Camunda form-js GitHub README — form field type list
- Flowable user task documentation — UEL expression pattern
- Activiti/Flowable community — variable scoping is the #1 source of production bugs in custom BPMN engines
- Zod GitHub releases — v4.3.6 confirmed latest stable; `z.fromJSONSchema()` added in 4.3.0

### Tertiary (supporting project docs)
- `/Users/leleka/Projects/procivo/docs/TASK_ASSIGNMENT_SPEC.md` — competitor patterns (Camunda, Jira, Appian, Bitrix24, ProcessMaker, Bonita, BPMN 2.0 spec)
- `/Users/leleka/Projects/procivo/docs/WORKFLOW_TASKS_INTEGRATION_PLAN.md` — architectural decisions and data flow (project-authored)
- `/Users/leleka/Projects/procivo/.planning/codebase/CONCERNS.md` — identified architectural concerns

---

*Research completed: 2026-02-28*
*Ready for roadmap: yes*
