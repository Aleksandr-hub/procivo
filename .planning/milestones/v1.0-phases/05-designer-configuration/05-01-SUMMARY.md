---
phase: 05-designer-configuration
plan: 01
subsystem: ui
tags: [vue3, workflow, validation, i18n, vueflow]

requires:
  - phase: 04-frontend-task-integration
    provides: TaskNodeConfig, FormFieldsBuilder, TransitionPropertyPanel components

provides:
  - Canvas validation warnings for transitions with form_fields but no action_key
  - Canvas validation warnings for duplicate action_keys on same source task node
  - Definition re-fetch after node/transition save to prevent stale state
  - i18n keys validationTransitionNoActionKey and validationDuplicateActionKey

affects:
  - 05-02 (depends on stable designer save pipeline)

tech-stack:
  added: []
  patterns:
    - "useCanvasValidation accepts optional definition ref for transition-level validation"
    - "WorkflowDesigner emits definition-changed event for parent re-fetch orchestration"
    - "Parent page (ProcessDesignerPage) owns definition fetch lifecycle — designer only signals"

key-files:
  created: []
  modified:
    - frontend/src/modules/workflow/composables/useCanvasValidation.ts
    - frontend/src/modules/workflow/components/WorkflowDesigner.vue
    - frontend/src/modules/workflow/pages/ProcessDesignerPage.vue
    - frontend/src/i18n/locales/uk.json
    - frontend/src/i18n/locales/en.json

key-decisions:
  - "definition re-fetch owned by parent page (ProcessDesignerPage), not WorkflowDesigner — separation of concerns"
  - "definition-changed emitted on both node save and transition update — covers all config persistence paths"
  - "duplicate action_key check scoped to task nodes only — gateways and start/end don't have action_keys"

patterns-established:
  - "Composable validation: pass optional Ref<ApiDTO | null> for API-sourced checks alongside graph-structure checks"
  - "Event-driven re-fetch: child emits notification, parent owns fetch lifecycle"

requirements-completed:
  - DSGN-01
  - DSGN-02
  - DSGN-03

duration: 3min
completed: 2026-03-01
---

# Phase 05 Plan 01: Designer Configuration - Validation + Persistence Summary

**Canvas validation for transitions with missing/duplicate action_key, and definition re-fetch after node/transition save to keep designer panels in sync**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-03-01T12:36:52Z
- **Completed:** 2026-03-01T12:40:13Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments

- `useCanvasValidation` now accepts an optional `definition` ref and validates transitions: warns when `form_fields` are configured but `action_key` is missing, and warns when duplicate `action_key` values are found on outgoing transitions from the same task node
- `WorkflowDesigner` emits `definition-changed` event after successful node save and after transition update, allowing the parent page to trigger a fresh API fetch
- `ProcessDesignerPage` listens to `definition-changed` and calls `store.fetchDefinition()` ensuring `definition.transitions` (with `form_fields` and `action_keys`) is always fresh for validation and panel rendering

## Task Commits

Each task was committed atomically:

1. **Task 1: Add canvas validation warning for transitions missing action_key** - `fd72d7a` (feat)
2. **Task 2: Ensure node config persistence across save and page reload** - `786dfe9` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified

- `frontend/src/modules/workflow/composables/useCanvasValidation.ts` - Added optional `definition` parameter; added `validationTransitionNoActionKey` and `validationDuplicateActionKey` checks
- `frontend/src/modules/workflow/components/WorkflowDesigner.vue` - Added `definition-changed` emit; pass `definitionRef` to useCanvasValidation; emit after node save and transition update
- `frontend/src/modules/workflow/pages/ProcessDesignerPage.vue` - Added `onDefinitionChanged` handler; wire `@definition-changed` event on WorkflowDesigner
- `frontend/src/i18n/locales/uk.json` - Added `validationTransitionNoActionKey` and `validationDuplicateActionKey` strings
- `frontend/src/i18n/locales/en.json` - Added `validationTransitionNoActionKey` and `validationDuplicateActionKey` strings

## Decisions Made

- Definition re-fetch owned by parent page (ProcessDesignerPage) — WorkflowDesigner only signals the need; parent decides how to fetch. This keeps designer as a pure presentation component.
- `definition-changed` emitted on both node save AND transition update — both paths can affect transition `form_fields`/`action_key` which validation depends on.
- Duplicate action_key check scoped to `task` node type only — only task nodes have outgoing action buttons; gateway/start/end don't use action_keys.

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

- 18 pre-existing TypeScript errors in unrelated files (organization module, tasks module). None in the workflow module. No new errors introduced.

## Next Phase Readiness

- Plan 05-01 hardened the save pipeline and validation layer; Plan 05-02 can build on stable foundation
- Designer now warns users about misconfigured transitions before publish
- Re-fetch pattern prevents stale state for any subsequent designer configuration work

## Self-Check: PASSED

- FOUND: frontend/src/modules/workflow/composables/useCanvasValidation.ts
- FOUND: frontend/src/modules/workflow/components/WorkflowDesigner.vue
- FOUND: frontend/src/modules/workflow/pages/ProcessDesignerPage.vue
- FOUND: .planning/phases/05-designer-configuration/05-01-SUMMARY.md
- FOUND commit fd72d7a: feat(05-01): add canvas validation for transitions missing action_key
- FOUND commit 786dfe9: feat(05-01): emit definition-changed after node/transition save for fresh re-fetch

---
*Phase: 05-designer-configuration*
*Completed: 2026-03-01*
