---
phase: 06-process-polish
plan: 04
subsystem: ui
tags: [vue3, primevue, useConfirm, progressbar, i18n, layout]

# Dependency graph
requires:
  - phase: 06-process-polish
    provides: existing ProcessInstanceDetailPage, ProcessContextCard, TaskDetailContent components
provides:
  - Cancel confirmation dialog with useConfirm on ProcessInstanceDetailPage
  - Correct ProgressBar percentage formula (Math.round) in ProcessContextCard
  - totalStepCount prop contract between TaskDetailContent and ProcessContextCard
  - Consistent layout spacing (1.5rem gap/margin) and normalized font sizes
affects: [06-process-polish]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "useConfirm from primevue/useconfirm for dangerous action confirmation"
    - "ProgressBar percentage as Math.round((completed/total)*100) with zero-guard"

key-files:
  created: []
  modified:
    - frontend/src/modules/workflow/pages/ProcessInstanceDetailPage.vue
    - frontend/src/modules/tasks/components/ProcessContextCard.vue
    - frontend/src/modules/tasks/components/TaskDetailContent.vue
    - frontend/src/i18n/locales/uk.json
    - frontend/src/i18n/locales/en.json

key-decisions:
  - "Used common.cancel (existing key) as rejectLabel instead of common.back (missing key) — avoids adding unnecessary i18n key"
  - "totalStepCount defaults to 1 (not 0) to prevent division-by-zero when stepperSteps is empty"
  - "Normalized context-label font-size from 0.7rem to 0.75rem for consistency with plan spec"

patterns-established:
  - "Confirm pattern: useConfirm wraps cancelInstance(), acceptClass: p-button-danger for destructive actions"
  - "ProgressBar: always use Math.round((completed/total)*100) with totalStepCount guard"

requirements-completed: [PLSH-04, PLSH-06]

# Metrics
duration: 3min
completed: 2026-03-01
---

# Phase 6 Plan 04: Cancel Confirmation + ProgressBar Fix Summary

**Cancel confirmation dialog with useConfirm on ProcessInstanceDetailPage and corrected ProgressBar percentage using Math.round((completed/total)*100) with totalStepCount prop**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-03-01T13:42:03Z
- **Completed:** 2026-03-01T13:44:33Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- Cancel button on process instance detail page now shows a PrimeVue confirmation dialog before executing the dangerous action
- ProgressBar formula replaced from broken `completedStepCount * 10` to correct `Math.round((completedStepCount / totalStepCount) * 100)` with division-by-zero guard
- `totalStepCount` prop added to `ProcessContextCard` and computed from `stepperSteps.length` in `TaskDetailContent`
- Layout polished: ProcessContextCard padding normalized to `1.25rem 1.5rem`, `context-label` font-size standardized to `0.75rem`, `.section` margin-bottom increased to `1.5rem`
- i18n keys `workflow.confirmCancelInstance` and `workflow.cancelProcess` added to both `uk.json` and `en.json`

## Task Commits

Each task was committed atomically:

1. **Task 1: Add cancel confirmation dialog to ProcessInstanceDetailPage** - `03b8729` (feat)
2. **Task 2: Fix ProgressBar percentage and polish task detail layout** - `b92ec77` (fix)

**Plan metadata:** (docs commit below)

## Files Created/Modified
- `frontend/src/modules/workflow/pages/ProcessInstanceDetailPage.vue` - Added useConfirm import, confirmCancel() function, updated cancel button @click handler
- `frontend/src/modules/tasks/components/ProcessContextCard.vue` - Added totalStepCount prop, fixed ProgressBar formula, adjusted padding and font-size
- `frontend/src/modules/tasks/components/TaskDetailContent.vue` - Added totalStepCount computed, passed prop to ProcessContextCard, increased section margin
- `frontend/src/i18n/locales/uk.json` - Added workflow.confirmCancelInstance and workflow.cancelProcess keys
- `frontend/src/i18n/locales/en.json` - Added workflow.confirmCancelInstance and workflow.cancelProcess keys

## Decisions Made
- Used `common.cancel` (existing key) as `rejectLabel` instead of `common.back` (which does not exist in i18n) — avoids adding unnecessary keys. Plan said "check if common.back exists first" — it did not.
- `totalStepCount` defaults to `1` via `stepperSteps.value.length || 1` to prevent division-by-zero when no steps are loaded yet.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Used existing common.cancel instead of missing common.back**
- **Found during:** Task 1 (Add cancel confirmation dialog)
- **Issue:** Plan specified `rejectLabel: t('common.back')` but `common.back` does not exist in either uk.json or en.json. Using a missing key would display the raw key string in the UI.
- **Fix:** Used `t('common.cancel')` (existing key) as rejectLabel — consistent with how TaskDetailContent.vue handles similar patterns.
- **Files modified:** `frontend/src/modules/workflow/pages/ProcessInstanceDetailPage.vue`
- **Verification:** TypeScript type check and vite build passed without errors.
- **Committed in:** `03b8729` (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (Rule 1 - Bug)
**Impact on plan:** Necessary correctness fix. No scope creep. All PLSH-04 and PLSH-06 requirements met.

## Issues Encountered
- None — TypeScript diagnostics caught missing `totalStepCount` prop in template immediately and were fixed inline before commit.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- PLSH-04 (Cancel confirmation) and PLSH-06 (ProgressBar + layout) are complete
- ProcessContextCard now has a clean, correct prop contract for future enhancements
- No blockers for remaining Phase 6 plans

---
*Phase: 06-process-polish*
*Completed: 2026-03-01*
