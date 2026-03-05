---
phase: 11-timer-execution
plan: "03"
subsystem: workflow
tags: [symfony, dbal, vue3, primevue, i18n, timer, process-instance]

# Dependency graph
requires:
  - phase: 11-01
    provides: workflow_scheduled_timers table with token_id and fire_at columns
provides:
  - GetProcessInstanceHandler enriches tokens with fire_at from workflow_scheduled_timers
  - ProcessInstanceDetailPage shows Overdue/Deadline badges for waiting timer tokens
affects: [11-future-plans, process-monitoring]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Token enrichment via secondary DBAL query with map lookup in fromRow()"
    - "isOverdue/formatDeadline helpers for relative time display"
    - "Intl-free relative time formatting with tiered thresholds (min/h/d)"

key-files:
  created: []
  modified:
    - backend/src/Workflow/Application/Query/GetProcessInstance/GetProcessInstanceHandler.php
    - backend/src/Workflow/Application/DTO/ProcessInstanceDTO.php
    - frontend/src/modules/workflow/types/process-instance.types.ts
    - frontend/src/modules/workflow/pages/ProcessInstanceDetailPage.vue
    - frontend/src/i18n/locales/en.json
    - frontend/src/i18n/locales/uk.json

key-decisions:
  - "Query pending timers separately (not JOIN) to keep fromRow signature clean — secondary DBAL query with timerMap lookup"
  - "timerFireAtMap defaults to [] in fromRow() — ListProcessInstancesHandler callers unaffected without changes"
  - "Deadline badge shown only when status==='waiting' AND fire_at present — non-timer tokens show nothing"

patterns-established:
  - "DTO enrichment pattern: fromRow(row, enrichmentMap=[]) for optional secondary data"
  - "Relative time badge: isOverdue() + formatDeadline() helpers in Vue SFC"

requirements-completed:
  - TIMR-04

# Metrics
duration: 12min
completed: 2026-03-05
---

# Phase 11 Plan 03: Timer Deadline Visibility Summary

**Backend enriches process instance tokens with fire_at from workflow_scheduled_timers; frontend shows Overdue (red) or deadline countdown (blue) Tag badges on ProcessInstanceDetailPage**

## Performance

- **Duration:** ~12 min
- **Started:** 2026-03-05T18:00:00Z
- **Completed:** 2026-03-05T18:12:00Z
- **Tasks:** 2
- **Files modified:** 6

## Accomplishments
- GetProcessInstanceHandler queries workflow_scheduled_timers for pending (fired_at IS NULL) timer fire_at per token
- ProcessInstanceDTO.fromRow() enriched with optional timerFireAtMap parameter — all tokens get fire_at (null for non-timer)
- ProcessInstanceDetailPage renders danger Tag "Overdue" for passed fire_at, info Tag with countdown for future fire_at
- i18n keys added (timerOverdue, timerDeadlineSoon, timerMinutes, timerHours, timerDaysUnit, tokenDeadline) in both en.json and uk.json

## Task Commits

Each task was committed atomically:

1. **Task 1: Enrich ProcessInstanceDTO tokens with fire_at** - `c540727` (feat)
2. **Task 2: Add overdue/deadline badges to ProcessInstanceDetailPage** - `edd6195` (feat)

**Plan metadata:** to be added in final docs commit

## Files Created/Modified
- `backend/src/Workflow/Application/Query/GetProcessInstance/GetProcessInstanceHandler.php` - Added secondary DBAL query for pending timers, passes timerMap to fromRow()
- `backend/src/Workflow/Application/DTO/ProcessInstanceDTO.php` - fromRow() accepts optional timerFireAtMap, enriches each token with fire_at
- `frontend/src/modules/workflow/types/process-instance.types.ts` - ProcessInstanceTokenDTO extended with optional fire_at field
- `frontend/src/modules/workflow/pages/ProcessInstanceDetailPage.vue` - isOverdue/formatDeadline helpers, Deadline column with Tag badges in tokens DataTable
- `frontend/src/i18n/locales/en.json` - Added timerOverdue, timerDeadlineIn, timerDeadlineSoon, timerMinutes, timerHours, timerDaysUnit, tokenDeadline
- `frontend/src/i18n/locales/uk.json` - Added Ukrainian translations for timer deadline keys

## Decisions Made
- Queried pending timers via separate DBAL fetchAllAssociative (not JOIN on view) to keep ProcessInstanceDTO.fromRow() composable — callers without timerMap (e.g., ListProcessInstancesHandler) are unaffected
- timerFireAtMap defaults to empty array — backward-compatible, zero changes needed in other callers
- Deadline badge only shown when both conditions hold: `token.fire_at && token.status === 'waiting'` — completed/cancelled tokens never show badge even if old fire_at exists

## Deviations from Plan

**1. [Rule 2 - Missing Critical] Added tokenDeadline i18n key for DataTable column header**
- **Found during:** Task 2 (frontend badge implementation)
- **Issue:** Plan specified badge-level i18n keys but did not include the column header key `tokenDeadline`
- **Fix:** Added `tokenDeadline: "Deadline"` / `tokenDeadline: "Дедлайн"` to both locale files
- **Files modified:** en.json, uk.json
- **Verification:** TypeScript compiles without errors, column header renders correctly
- **Committed in:** edd6195 (Task 2 commit)

---

**Total deviations:** 1 auto-fixed (missing column header i18n key)
**Impact on plan:** Necessary for correct UI rendering. No scope creep.

## Issues Encountered
None - execution proceeded smoothly.

## Next Phase Readiness
- Phase 11 Plan 03 complete — timer deadline visibility for process administrators is live
- All 3 plans of Phase 11 complete: DB-persistent scheduling (11-01), timer config UI (11-02), deadline visibility (11-03)
- Phase 11 fully done — ready for Phase 12 (Impersonation / next milestone planning)

## Self-Check: PASSED

All files verified present. Both task commits verified in git history.

---
*Phase: 11-timer-execution*
*Completed: 2026-03-05*
