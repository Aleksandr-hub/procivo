---
phase: 06-process-polish
plan: 03
subsystem: workflow
tags: [pagination, search, postgresql, ilike, primevue, datatable, pinia]

# Dependency graph
requires:
  - phase: 06-process-polish
    provides: ProcessInstancesPage with client-side pagination and status filter

provides:
  - Server-side ILIKE search by definition name in ListProcessInstancesHandler
  - LIMIT/OFFSET pagination with total count in paginated response {items, total, page, limit}
  - ListProcessInstancesQuery with search, page, limit parameters
  - ProcessInstanceController extracting search, page, limit query params
  - PaginatedResponse<T> and ListProcessInstancesParams types in API client
  - process-instance.store with total ref
  - ProcessInstancesPage lazy DataTable with server-side page fetching
  - Search InputText with 300ms debounce above DataTable

affects: [07-task-polish, 08-audit, 09-notifications]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "ILIKE search via DBAL clone-count-then-paginate pattern"
    - "PrimeVue lazy DataTable with 0-to-1-indexed page translation"
    - "300ms debounce watch for search input reset to page 1"

key-files:
  created: []
  modified:
    - backend/src/Workflow/Application/Query/ListProcessInstances/ListProcessInstancesQuery.php
    - backend/src/Workflow/Application/Query/ListProcessInstances/ListProcessInstancesHandler.php
    - backend/src/Workflow/Presentation/Controller/ProcessInstanceController.php
    - frontend/src/modules/workflow/api/process-instance.api.ts
    - frontend/src/modules/workflow/stores/process-instance.store.ts
    - frontend/src/modules/workflow/pages/ProcessInstancesPage.vue
    - frontend/src/i18n/locales/uk.json
    - frontend/src/i18n/locales/en.json

key-decisions:
  - "Clone QueryBuilder for COUNT(*) before applying LIMIT/OFFSET to avoid subquery complexity"
  - "Clamp limit to 1-100 and page minimum 1 in controller for safety"
  - "PrimeVue DataTable page event is 0-indexed; translate to 1-indexed for backend"
  - "Status filter and search both reset currentPage to 1 and first to 0 on change"

patterns-established:
  - "Paginated list pattern: clone QB for count, then apply LIMIT/OFFSET, return {items, total, page, limit}"
  - "Frontend lazy DataTable: first ref tracks offset, onPage handler updates currentPage and calls loadInstances()"

requirements-completed: [PLSH-05]

# Metrics
duration: 22min
completed: 2026-03-01
---

# Phase 6 Plan 03: Server-Side Search and Pagination for Process Instances Summary

**ILIKE search by definition name + LIMIT/OFFSET pagination via DBAL clone-count pattern, with lazy PrimeVue DataTable and 300ms debounced search input**

## Performance

- **Duration:** 22 min
- **Started:** 2026-03-01T13:41:53Z
- **Completed:** 2026-03-01T14:03:42Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments

- Backend ListProcessInstances now returns `{items, total, page, limit}` with ILIKE search and LIMIT/OFFSET
- Frontend API and store updated to handle paginated response with `total` ref
- ProcessInstancesPage converted to lazy DataTable with server-side pagination and debounced search input

## Task Commits

Each task was committed atomically:

1. **Task 1: Backend search and pagination** - `ac4dda0` (feat)
2. **Task 2: Frontend API, store, and page update** - `6575a84` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified

- `backend/src/Workflow/Application/Query/ListProcessInstances/ListProcessInstancesQuery.php` - Added search, page, limit parameters
- `backend/src/Workflow/Application/Query/ListProcessInstances/ListProcessInstancesHandler.php` - ILIKE filter, clone-count, LIMIT/OFFSET, paginated return
- `backend/src/Workflow/Presentation/Controller/ProcessInstanceController.php` - Extract search/page/limit from query string, clamped values
- `frontend/src/modules/workflow/api/process-instance.api.ts` - ListProcessInstancesParams, PaginatedResponse<T> types; updated list()
- `frontend/src/modules/workflow/stores/process-instance.store.ts` - Added total ref; fetchInstances accepts params object
- `frontend/src/modules/workflow/pages/ProcessInstancesPage.vue` - Lazy DataTable, search InputText, 300ms debounce, page reset on filter change
- `frontend/src/i18n/locales/uk.json` - Added searchInstances key
- `frontend/src/i18n/locales/en.json` - Added searchInstances key

## Decisions Made

- Clone QueryBuilder for COUNT(*) before applying LIMIT/OFFSET to keep query simple (no subquery needed)
- Clamp limit to 1-100 and page minimum 1 in controller for safety
- PrimeVue DataTable `page` event provides 0-indexed page; translate to 1-indexed (+1) for backend
- Status filter and search both reset `currentPage` to 1 and `first` to 0 on change to avoid empty pages

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- PLSH-05 complete: process instance list is production-ready with server-side search and pagination
- Pattern established for other paginated lists (can be applied to task lists, definitions, etc.)
- Ready for Phase 6 Plan 04

## Self-Check: PASSED

All files verified present. Both task commits confirmed in git history.

---
*Phase: 06-process-polish*
*Completed: 2026-03-01*
