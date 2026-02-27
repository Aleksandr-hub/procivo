# Procivo — Workflow + Tasks Integration

## What This Is

Procivo is a BPM (Business Process Management) platform built with Symfony 8 + Vue 3 + PrimeVue 4. It combines task management (boards, kanban, assignments), a custom BPMN workflow engine (designer, process execution with token-based state machine), and organizational structure (departments, roles, positions). This milestone completes the integration between the Workflow and TaskManager modules — making workflow tasks interactive with dynamic forms, action-based completion, gateway condition evaluation, and task assignment strategies.

## Core Value

Users can execute BPMN processes end-to-end: start a process, fill out task forms, choose actions (approve/reject/etc.), have the process branch based on form data, and complete — with proper task assignment and pool task claiming.

## Requirements

### Validated

<!-- Shipped and confirmed valuable. -->

- ✓ User registration, authentication, JWT tokens with refresh rotation — Phase 1
- ✓ Organization structure: departments, roles, positions, employees — Phase 1
- ✓ RBAC permission model with organization-scoped authorization — Phase 1
- ✓ Task CRUD with status state machine (draft → open → in_progress → review → done) — Phase 2
- ✓ Kanban boards with columns and drag-and-drop — Phase 2
- ✓ Task comments and attachments (S3 file storage) — Phase 2
- ✓ Task labels and filtering — Phase 2
- ✓ Workflow Designer: vue-flow canvas, node palette, property panels — Phase 3
- ✓ Process definition CRUD with versioning — Phase 3
- ✓ 6 process templates (simple task, doc approval, HR onboarding, leave, invoice, bug fix) — Phase 3
- ✓ WorkflowEngine: token-based execution with event sourcing — Phase 3
- ✓ ProcessGraphValidator: validates process definitions before deployment — Phase 3
- ✓ OnTaskNodeActivated creates tasks when process reaches Task node — Phase 3
- ✓ WorkflowTaskLink bridges Workflow and TaskManager modules — Phase 3
- ✓ Node types: Start, End, Task, Gateways (XOR/Parallel/Inclusive), Timer, Notification, Webhook, SubProcess — Phase 3
- ✓ Notifications module with Mercure real-time updates — Phase 3
- ✓ Frontend i18n (Ukrainian + English) — Phase 1
- ✓ DynamicFormField.vue component exists (basic) — Phase 3

### Active

<!-- Current scope. Building toward these. -->

- [ ] Backend: form_schema built from TaskNode config + outgoing transitions, stored in Task metadata (JSONB)
- [ ] Backend: CompleteTaskNodeCommand accepts { action, formData }, validates required fields, merges into ProcessInstance.variables
- [ ] Backend: Extended form validation (required, type, min/max, regex patterns, field dependencies)
- [ ] Backend: ExpressionEvaluator integrated with XOR gateway — evaluates conditions against ProcessInstance.variables using Symfony ExpressionLanguage
- [ ] Backend: Task assignment strategies (unassigned, specific_employee, process_initiator, by_role, by_department)
- [ ] Backend: Claim/unclaim mechanism for pool tasks
- [ ] Backend: API GET /api/v1/tasks/{id} returns form_schema
- [ ] Backend: API POST /api/v1/tasks/{id}/complete with { action, formData }
- [ ] Frontend: Task detail page with dynamic form rendering per action
- [ ] Frontend: Action buttons from form_schema.actions (e.g., "Approve", "Reject")
- [ ] Frontend: ActionFormDialog — dynamic fields per action + shared fields + comment + next assignment selector
- [ ] Frontend: Process context on task cards (process name badge, current stage)
- [ ] Frontend: Process history timeline on task detail
- [ ] Frontend: Pool task banner with claim/assign buttons and candidate list
- [ ] Frontend: "Start Process" button from tasks page
- [ ] Frontend: Assignment configuration in Workflow Designer (TaskNodeConfig)

### Out of Scope

<!-- Explicit boundaries. Includes reasoning to prevent re-adding. -->

- Mobile app — web-first, mobile later
- Parallel gateway full implementation — XOR gateway is priority for this milestone
- SubProcess execution — node type exists but full execution deferred
- Advanced timer events — basic timer exists, complex scheduling deferred
- Process versioning migration (live instances) — only new instances use new versions
- Dark mode — PrimeVue theming deferred to future milestone

## Context

- Figma Make prototype with UI reference: https://www.figma.com/make/cgshZil5qRJ31B5vWKsIkE/Analyze-system-file
  - Key components: TaskList, TaskDetail, ActionDialog, TaskAssignmentBadge
  - Types: ActionConfig, FormField, NextAssignmentConfig
  - Design intent: Linear/Notion-style clean UI with action buttons in header, pool task banners, process history timeline
- Existing plan documents: `docs/WORKFLOW_TASKS_INTEGRATION_PLAN.md`, `docs/TASK_ASSIGNMENT_SPEC.md`
- Key architectural decision: forms per ACTION (transition), not per task. Different actions = different form fields
- ExpressionEvaluator service exists but is not integrated with gateway execution yet
- DynamicFormField.vue exists with basic field rendering
- TaskNodeConfig.vue exists with basic task node configuration in designer
- Several new files already started (visible in git status): ClaimTask, UnclaimTask commands, AssignmentStrategy VO, OrganizationQueryPort, etc.
- Process variables stored as JSONB in ProcessInstance entity
- Frontend uses PrimeVue 4 components (NOT shadcn/Tailwind from Figma prototype — adapt design intent to PrimeVue)

## Constraints

- **Tech stack**: Symfony 8 + Vue 3 + PrimeVue 4 + TypeScript (no stack changes)
- **Architecture**: Clean Architecture + DDD + CQRS — all changes must follow existing patterns
- **Doctrine**: XML mappings only (no annotations/attributes for entities)
- **Frontend UI**: PrimeVue components — Figma prototype is React+shadcn reference, must be adapted
- **Time**: 1-2 hours/day (~10h/week) — pet project pace
- **API style**: Manual REST controllers (no API Platform)
- **Buses**: 3 Messenger buses (command.bus, query.bus, event.bus) — handlers decorated with #[AsMessageHandler]

## Key Decisions

<!-- Decisions that constrain future work. Add throughout project lifecycle. -->

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Forms per ACTION (transition), not per task | Different actions need different fields (approve vs reject) | — Pending |
| Symfony ExpressionLanguage for conditions | Full EL power — functions, methods, arrays | — Pending |
| Extended form validation (required + type + min/max + regex + field dependencies) | Production-quality forms need proper validation | — Pending |
| Pool tasks with claim/unclaim | Enterprise BPM standard for group assignments | — Pending |
| PrimeVue adaptation of Figma prototype | Keep consistent UI library, adapt design intent | — Pending |

---
*Last updated: 2026-02-27 after initialization*
