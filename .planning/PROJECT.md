# Procivo — BPM Platform

## What This Is

Procivo is a BPM (Business Process Management) platform built with Symfony 8 + Vue 3 + PrimeVue 4. It combines task management (boards, kanban, assignments), a custom BPMN workflow engine (designer, process execution with token-based state machine), and organizational structure (departments, roles, positions). Users can design processes with forms and assignment rules in the visual designer, start processes, fill out dynamic task forms, choose actions, have the process branch based on form data via XOR gateways, claim pool tasks, and complete processes end-to-end.

## Core Value

Users can execute BPMN processes end-to-end: design → publish → start → fill task forms → choose actions → gateway routing → complete — with proper task assignment and pool task claiming.

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
- ✓ Form schema built from TaskNode config + outgoing transitions, stored in Task JSONB — v1.0
- ✓ Task completion API: POST /tasks/{id}/complete with action + formData validation — v1.0
- ✓ Extended form validation (required, type, min/max, regex, field dependencies) — v1.0
- ✓ ExpressionEvaluator integrated with XOR gateway using Symfony ExpressionLanguage — v1.0
- ✓ Assignment strategies (unassigned, specific_employee, by_role, by_department) — v1.0
- ✓ Claim/unclaim mechanism for pool tasks with pessimistic locking — v1.0
- ✓ Task detail page with dynamic form rendering per action — v1.0
- ✓ ActionFormDialog with Zod validation, action-typed buttons, optional comment — v1.0
- ✓ Process context on task cards (process name badge, current stage) — v1.0
- ✓ Process history timeline on task detail — v1.0
- ✓ Pool task banner with claim/assign buttons and candidate list — v1.0
- ✓ ProcessContextCard (process name, stage, progress, next step hint) — v1.0
- ✓ MyPathStepper (token path visualization, adaptive display) — v1.0
- ✓ Process navigation ("View Full Process" button) — v1.0
- ✓ Assignment strategy selector in Workflow Designer (TaskNodeConfig) — v1.0
- ✓ Per-transition form field builder in designer (FormFieldsBuilder) — v1.0
- ✓ Canvas validation warnings (missing action_key, duplicate action_keys) — v1.0

### Active

<!-- Current scope. Building toward these in next milestone. -->

(To be defined in next milestone via `/gsd:new-milestone`)

### Out of Scope

<!-- Explicit boundaries. Includes reasoning to prevent re-adding. -->

- Mobile app — web-first, mobile later
- Parallel gateway full implementation — XOR gateway is priority; parallel adds significant complexity
- SubProcess full execution — node type exists but cascading execution deferred
- Advanced timer events — basic timer exists, complex scheduling deferred
- Process versioning migration (live instances) — only new instances use new versions
- Dark mode — PrimeVue theming deferred to future milestone
- File upload in form fields — form fields are data-entry only for now
- Mobile-responsive task forms — desktop-first

## Context

Shipped v1.0 with ~49K LOC (30.7K PHP, 14.6K Vue, 3.8K TypeScript).
Tech stack: Symfony 8, Vue 3, PrimeVue 4, PostgreSQL, Redis, RabbitMQ, Mercure.
Architecture: Clean Architecture, DDD, CQRS, Event Sourcing (Workflow), Modular Monolith.

Known tech debt from v1.0:
- Task.formSchema snapshot written but not consumed by frontend (uses live schema from workflow context)
- Duplicate schema-building logic in GetTaskWorkflowContextHandler vs FormSchemaBuilder
- from_variable strategy in frontend dropdown not backed by AssignmentStrategy enum
- Phase 4 missing formal VERIFICATION.md (code confirmed working via integration check)

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
| Forms per ACTION (transition), not per task | Different actions need different fields (approve vs reject) | ✓ Good — clean separation, each action gets its own field set |
| Symfony ExpressionLanguage for conditions | Full EL power — functions, methods, arrays | ✓ Good — design-time lint + runtime evaluation working |
| Extended form validation (required + type + min/max + regex + field dependencies) | Production-quality forms need proper validation | ✓ Good — iterative dependency resolution handles cascading visibility |
| Pool tasks with claim/unclaim | Enterprise BPM standard for group assignments | ✓ Good — pessimistic locking prevents double-claim |
| PrimeVue adaptation of Figma prototype | Keep consistent UI library, adapt design intent | ✓ Good — full-page list + action dialogs match design intent |
| Variable namespacing by node ID | Prevents key collisions across stages | ✓ Good — dual-layer (namespaced + flat aliases) |
| Form schema snapshot at task creation | Prevents schema drift from definition updates | ⚠️ Revisit — snapshot written but frontend uses live schema |
| Custom domain validation (not Symfony Validator) | Simpler for dynamic JSON schema | ✓ Good — rule-per-type dispatch, easy to extend |
| Zod 4 for frontend validation | Type-safe schema builder from form definitions | ✓ Good — flattenError() API works well with PrimeVue |
| Definition re-fetch after save | Prevents stale state in designer | ✓ Good — parent page owns fetch, designer emits signal |

---
*Last updated: 2026-03-01 after v1.0 milestone*
