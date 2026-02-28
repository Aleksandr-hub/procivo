# Roadmap: Procivo — Workflow + Tasks Integration

## Overview

This milestone connects two existing modules — the Workflow Engine (token-based BPMN execution) and the TaskManager (CRUD tasks, kanban, assignments) — into a unified BPM platform. The work progresses bottom-up through the architectural layer dependency chain: backend foundation for variables and gateways, form schema embedding and assignment infrastructure, task completion and claim APIs, frontend task integration, and finally workflow designer configuration. Each phase delivers a verifiable capability that unblocks the next. By the end, users can design processes with forms and assignment rules, start processes, fill out dynamic task forms, choose actions, have gateways route based on submitted data, and claim pool tasks — the full BPM loop.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Backend Foundation** - Variable namespacing, XOR gateway condition evaluation, and form validation architecture
- [ ] **Phase 2: Form Schema and Assignment** - Task form schema creation-time embedding and assignment strategy resolution
- [ ] **Phase 3: Completion and Claim APIs** - Task completion endpoint with validation and pool task claim/unclaim
- [ ] **Phase 4: Frontend Task Integration** - Task detail UI with dynamic forms, action dialogs, pool task banner, process context card, My Path Stepper, and process navigation
- [ ] **Phase 5: Designer Configuration** - Assignment strategy and per-transition form field configuration in the Workflow Designer

## Phase Details

### Phase 1: Backend Foundation
**Goal**: Process variables flow correctly through namespaced merge, XOR gateways evaluate conditions safely against submitted data, and backend form validation infrastructure is ready for downstream use
**Depends on**: Nothing (first phase)
**Requirements**: GATE-01, GATE-02, GATE-03, GATE-04, COMP-02, COMP-03, COMP-05
**Success Criteria** (what must be TRUE):
  1. ExpressionEvaluator evaluates XOR gateway conditions against ProcessInstance.variables and selects the correct outgoing transition
  2. When a condition references an undefined variable, a structured warning is logged and the gateway does not silently mis-route (default/else branch taken)
  3. ProcessInstance.mergeVariables namespaces all form data by node ID, preventing key collisions between stages
  4. FormSchemaValidator validates field data against schema definitions (required, type, min/max, regex, field dependencies) and returns structured errors
**Plans**: 3 plans

Plans:
- [ ] 01-01-PLAN.md — ExpressionEvaluator enhancement (Throwable catch, structured logging) + ProcessInstance namespaced variable merging (TDD)
- [ ] 01-02-PLAN.md — FormSchemaValidator with type, constraint, regex, and field dependency validation (TDD)
- [ ] 01-03-PLAN.md — Design-time expression lint, ProcessGraphValidator expression validation, XOR gateway default branch hardening

### Phase 2: Form Schema and Assignment
**Goal**: When a process reaches a Task node, OnTaskNodeActivated builds and snapshots the full form_schema into the created Task, resolves assignment strategy, and the API returns form_schema to callers
**Depends on**: Phase 1
**Requirements**: FORM-01, FORM-02, FORM-03, FORM-04, ASGN-01, ASGN-02, ASGN-03, ASGN-04, ASGN-07
**Success Criteria** (what must be TRUE):
  1. OnTaskNodeActivated builds form_schema from TaskNode shared_fields and outgoing transition action-specific fields, and stores it as JSONB in the Task entity at creation time
  2. Each action in form_schema contains its own field set plus the shared fields from the task node
  3. GET /api/v1/tasks/{id} returns form_schema alongside task data
  4. AssignmentResolver resolves all 4 strategies (unassigned, specific_employee, by_role, by_department) and creates tasks with correct assigneeId or candidateRoleId/candidateDepartmentId
  5. Pool tasks with a single candidate are auto-assigned to that candidate
**Plans**: 2 plans

Plans:
- [ ] 02-01-PLAN.md — FormSchemaBuilder service + Task entity formSchema JSONB field + migration + CreateTaskCommand/Handler/DTO updates + unit tests
- [ ] 02-02-PLAN.md — Wire OnTaskNodeActivated to use FormSchemaBuilder + AssignmentResolver and CreateTaskHandler unit tests

### Phase 3: Completion and Claim APIs
**Goal**: Users can complete workflow tasks by submitting action + formData through the API, and pool tasks can be claimed/unclaimed with proper concurrency control
**Depends on**: Phase 2
**Requirements**: COMP-01, COMP-04, ASGN-05, ASGN-06
**Success Criteria** (what must be TRUE):
  1. POST /api/v1/tasks/{id}/complete accepts { action, formData }, validates data server-side against Task.form_schema, merges into process variables, and advances the workflow token to the next node via the selected action's transition
  2. POST /api/v1/tasks/{id}/claim assigns a pool task to the requesting employee with pessimistic locking to prevent double-claim
  3. POST /api/v1/tasks/{id}/unclaim returns a claimed task to the pool (assigneeId set back to null)
  4. Completing a task at an XOR gateway fork causes the engine to evaluate conditions against the updated variables and route to the correct branch
**Plans**: TBD

Plans:
- [ ] 03-01: TBD
- [ ] 03-02: TBD

### Phase 4: Frontend Task Integration
**Goal**: Users interact with workflow tasks through a polished UI: see dynamic forms per action, submit decisions, view process context with stepper and navigation, and claim pool tasks
**Depends on**: Phase 3
**Requirements**: FEND-01, FEND-02, FEND-03, FEND-04, FEND-05, FEND-06, FEND-07, FEND-08, FEND-09, FEND-10, FEND-11
**Success Criteria** (what must be TRUE):
  1. Task detail page renders dynamic form fields from form_schema using DynamicFormField.vue, with action buttons derived from form_schema.actions
  2. Clicking an action button opens ActionFormDialog showing action-specific fields + shared fields + optional comment, with Zod-based frontend validation and field-level error display
  3. Form submission (action + formData) calls POST /tasks/{id}/complete and the task disappears from the user's active list as the workflow advances
  4. Pool tasks display a banner with "Claim" / "Unclaim" buttons and candidate context (role/department members)
  5. Task cards in the list show process context badge (process name and current stage name), and the task detail includes a process history timeline tab
  6. Process Context Card displays process name, current stage, progress bar (X/Y steps), and "Next step: X" text on workflow task detail
  7. My Path Stepper renders horizontal stepper of the actual token path (completed/current/upcoming), with adaptive display: full (3-7 steps), scrollable (8-20), compact header + modal (20+)
  8. "View Full Process" button navigates to ProcessInstanceDetailPage; contextual hint shows next step name
**Plans**: TBD

Plans:
- [ ] 04-01: TBD
- [ ] 04-02: TBD

### Phase 5: Designer Configuration
**Goal**: Process designers can configure assignment strategies and per-action form fields directly in the Workflow Designer UI, closing the full design-to-execution loop
**Depends on**: Phase 4
**Requirements**: DSGN-01, DSGN-02, DSGN-03
**Success Criteria** (what must be TRUE):
  1. TaskNodeConfig panel includes an assignment strategy dropdown (unassigned, specific_employee, by_role, by_department) with dynamic sub-fields (employee/role/department selector) that appear based on the selected strategy
  2. Transition property panel includes FormFieldsBuilder for configuring per-action form fields, and the configured fields are saved to the process definition
  3. A process designed entirely through the UI (with assignment rules and form fields configured in the designer) can be started, executed through task forms, and completed end-to-end without any JSON editing
**Plans**: TBD

Plans:
- [ ] 05-01: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Backend Foundation | 0/? | Not started | - |
| 2. Form Schema and Assignment | 0/2 | Planned | - |
| 3. Completion and Claim APIs | 0/? | Not started | - |
| 4. Frontend Task Integration | 0/? | Not started | - |
| 5. Designer Configuration | 0/? | Not started | - |
