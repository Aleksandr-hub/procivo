# Requirements: Procivo — Workflow + Tasks Integration

**Defined:** 2026-02-28
**Core Value:** Users can execute BPMN processes end-to-end with interactive task forms, conditional branching, and proper task assignment

## v1 Requirements

Requirements for this milestone. Each maps to roadmap phases.

### Task Form Schema

- [ ] **FORM-01**: OnTaskNodeActivated builds form_schema from TaskNode config (shared_fields) and outgoing transitions (action-specific fields)
- [ ] **FORM-02**: form_schema is snapshotted into Task metadata (JSONB) at creation time to prevent schema drift
- [ ] **FORM-03**: Each action in form_schema has its own set of fields plus shared fields from the task node
- [ ] **FORM-04**: API GET /api/v1/tasks/{id} returns form_schema alongside task data

### Task Completion

- [ ] **COMP-01**: API POST /api/v1/tasks/{id}/complete accepts { action, formData }
- [ ] **COMP-02**: Backend validates formData against form_schema (required, type, min/max, regex patterns)
- [ ] **COMP-03**: Backend merges validated formData into ProcessInstance.variables with namespace prefix to prevent collisions
- [ ] **COMP-04**: After merge, workflow engine advances — token moves to next node via the selected action's transition
- [ ] **COMP-05**: Field dependency validation — show/require field X only when field Y has specific value

### Gateway Conditions

- [ ] **GATE-01**: ExpressionEvaluator integrated with XOR gateway — evaluates conditions against ProcessInstance.variables
- [ ] **GATE-02**: Full Symfony ExpressionLanguage support (operators, functions, arrays, null-coalescing)
- [ ] **GATE-03**: Undefined variables in expressions log warnings and evaluate safely (no silent mis-routing)
- [ ] **GATE-04**: Default/else branch on XOR gateway when no condition matches

### Task Assignment

- [ ] **ASGN-01**: Assignment strategies: unassigned, specific_employee, by_role, by_department
- [ ] **ASGN-02**: OnTaskNodeActivated resolves assignment strategy from node config and creates task with correct assignee/candidates
- [ ] **ASGN-03**: Pool tasks (by_role, by_department) — task created with candidateRoleId/candidateDepartmentId, assigneeId = null
- [ ] **ASGN-04**: Auto-assign when single candidate in pool
- [ ] **ASGN-05**: API POST /api/v1/tasks/{id}/claim — employee claims pool task (with pessimistic locking)
- [ ] **ASGN-06**: API POST /api/v1/tasks/{id}/unclaim — employee returns task to pool
- [ ] **ASGN-07**: OrganizationQueryPort anti-corruption layer — TaskManager queries Organization module for role/department members

### Frontend: Task Detail

- [ ] **FEND-01**: Task detail page renders form_schema as dynamic form using DynamicFormField.vue
- [ ] **FEND-02**: Action buttons displayed from form_schema.actions (e.g., "Approve", "Reject")
- [ ] **FEND-03**: ActionFormDialog opens on action click — shows action-specific fields + shared fields + optional comment
- [ ] **FEND-04**: Frontend form validation with Zod schema built from form_schema field definitions
- [ ] **FEND-05**: Form submission: action + formData → POST /api/v1/tasks/{id}/complete → workflow advances
- [ ] **FEND-06**: Pool task banner with claim/assign buttons and candidate list (adapted from Figma prototype)
- [ ] **FEND-07**: Process context badge on task cards in list (process name → current stage)
- [ ] **FEND-08**: Process history timeline tab on task detail page

### Designer Configuration

- [ ] **DSGN-01**: Assignment strategy selector in TaskNodeConfig (dropdown: unassigned, specific_employee, by_role, by_department)
- [ ] **DSGN-02**: Dynamic sub-fields based on strategy (employee selector, role selector, department selector)
- [ ] **DSGN-03**: Per-transition form field builder in designer (FormFieldsBuilder.vue already exists — wire to backend save)

## v2 Requirements

Deferred to future milestone. Tracked but not in current roadmap.

### Assignment Strategies (Advanced)

- **ASGN-V2-01**: process_initiator strategy — assign to person who started the process
- **ASGN-V2-02**: previous_performer strategy — assign to who completed a reference node
- **ASGN-V2-03**: by_manager strategy — assign to manager of initiator/previous performer

### Frontend (Advanced)

- **FEND-V2-01**: Visual process monitor graph on task detail (ProcessMonitorGraph.vue)
- **FEND-V2-02**: Start process dialog with start form schema
- **FEND-V2-03**: Real-time task updates via Mercure SSE

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Parallel gateway full UI implementation | XOR gateway is priority; parallel adds significant complexity |
| SubProcess full execution | Node type exists but cascading execution deferred |
| Advanced timer events (complex scheduling) | Basic timer sufficient for this milestone |
| Live process version migration | Only new instances use new definition versions |
| Dark mode | PrimeVue theming deferred to future milestone |
| Mobile-responsive task forms | Desktop-first; mobile later |
| File upload in form fields | Form fields are data-entry only for now |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| FORM-01 | Phase 2 | Pending |
| FORM-02 | Phase 2 | Pending |
| FORM-03 | Phase 2 | Pending |
| FORM-04 | Phase 2 | Pending |
| COMP-01 | Phase 3 | Pending |
| COMP-02 | Phase 1 | Pending |
| COMP-03 | Phase 1 | Pending |
| COMP-04 | Phase 3 | Pending |
| COMP-05 | Phase 1 | Pending |
| GATE-01 | Phase 1 | Pending |
| GATE-02 | Phase 1 | Pending |
| GATE-03 | Phase 1 | Pending |
| GATE-04 | Phase 1 | Pending |
| ASGN-01 | Phase 2 | Pending |
| ASGN-02 | Phase 2 | Pending |
| ASGN-03 | Phase 2 | Pending |
| ASGN-04 | Phase 2 | Pending |
| ASGN-05 | Phase 3 | Pending |
| ASGN-06 | Phase 3 | Pending |
| ASGN-07 | Phase 2 | Pending |
| FEND-01 | Phase 4 | Pending |
| FEND-02 | Phase 4 | Pending |
| FEND-03 | Phase 4 | Pending |
| FEND-04 | Phase 4 | Pending |
| FEND-05 | Phase 4 | Pending |
| FEND-06 | Phase 4 | Pending |
| FEND-07 | Phase 4 | Pending |
| FEND-08 | Phase 4 | Pending |
| DSGN-01 | Phase 5 | Pending |
| DSGN-02 | Phase 5 | Pending |
| DSGN-03 | Phase 5 | Pending |

**Coverage:**
- v1 requirements: 31 total
- Mapped to phases: 31
- Unmapped: 0

---
*Requirements defined: 2026-02-28*
*Last updated: 2026-02-28 after roadmap creation*
