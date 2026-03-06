---
phase: 13-granular-permissions-rbac
plan: 04
subsystem: ui
tags: [rbac, permissions, vue, primevue, pinia]

# Dependency graph
requires:
  - phase: 13-01
    provides: "DepartmentPermission/UserPermissionOverride entities and PermissionResolver"
  - phase: 13-03
    provides: "PermissionController REST API and /my-permissions endpoint"
provides:
  - "PermissionsPage with Roles/Departments/Users tabs for admin permission management"
  - "RolePermissionsTab with resource x action matrix grid"
  - "DepartmentPermissionsTab for department default permissions"
  - "UserOverridesTab with allow/deny override management"
  - "EffectivePermissionsView showing merged permission sources"
  - "ProcessAccessDialog for per-definition access control"
  - "Permission API client (permission.api.ts)"
  - "Permission store wired into app lifecycle (DashboardLayout)"
  - "Router navigation guard for permission-based route protection"
  - "UI permission guards on create buttons (tasks, workflow, invitations)"
affects: []

# Tech tracking
tech-stack:
  added: []
  patterns: ["Permission matrix grid: resources as rows, actions as columns, scope Select per cell", "Route meta.permission for declarative permission-based route guards", "Permission store fetch on orgId change via route params watcher"]

key-files:
  created:
    - frontend/src/modules/organization/api/permission.api.ts
    - frontend/src/modules/organization/pages/PermissionsPage.vue
    - frontend/src/modules/organization/components/RolePermissionsTab.vue
    - frontend/src/modules/organization/components/DepartmentPermissionsTab.vue
    - frontend/src/modules/organization/components/UserOverridesTab.vue
    - frontend/src/modules/organization/components/EffectivePermissionsView.vue
    - frontend/src/modules/organization/components/ProcessAccessDialog.vue
  modified:
    - frontend/src/modules/organization/types/organization.types.ts
    - frontend/src/modules/organization/stores/permission.store.ts
    - frontend/src/shared/layouts/DashboardLayout.vue
    - frontend/src/shared/components/AppSidebar.vue
    - frontend/src/router/index.ts
    - frontend/src/i18n/locales/en.json
    - frontend/src/i18n/locales/uk.json
    - frontend/src/modules/organization/pages/EmployeesPage.vue
    - frontend/src/modules/organization/pages/OrganizationDetailPage.vue
    - frontend/src/modules/tasks/pages/TasksPage.vue
    - frontend/src/modules/workflow/pages/ProcessDefinitionsPage.vue

key-decisions:
  - "Permission matrix uses resource rows x action columns with scope Select dropdown per cell — diff-based save via grant/revoke API"
  - "Router navigation guard allows navigation when permissions not yet loaded (loaded=false) — avoids blocking first render"
  - "Permission store fetch triggered by route.params.orgId watcher in DashboardLayout — immediate:true for page refresh"
  - "Sidebar permissions link uses permissionStore.can('role', 'view') || permissionStore.isOwner for visibility gating"

patterns-established:
  - "Permission matrix grid: HTML table with Select per cell for scope selection"
  - "Route meta.permission: { resource, action } for declarative permission checks in beforeEach guard"
  - "v-if=\"permissionStore.can(resource, action)\" pattern for UI element gating"

requirements-completed: [PERM-04]

# Metrics
duration: 8min
completed: 2026-03-06
---

# Phase 13 Plan 04: Frontend Permissions UI & Permission-Gated Elements Summary

**Permissions admin page with role/department/user tabs, permission matrix grid, effective permissions view, and app-wide permission store lifecycle with UI gating**

## Performance

- **Duration:** 8 min
- **Started:** 2026-03-06T06:54:11Z
- **Completed:** 2026-03-06T07:02:28Z
- **Tasks:** 2
- **Files modified:** 18

## Accomplishments
- Created PermissionsPage with 3 tabs (Roles, Departments, Users) for full permission hierarchy management
- Built permission matrix grid with resources as rows, actions as columns, and scope Select dropdowns per cell
- Added UserOverridesTab with allow/deny override CRUD and EffectivePermissionsView showing merged permission sources
- Created ProcessAccessDialog for per-definition access control (view/start rules with department/role pairs)
- Wired permission store into DashboardLayout lifecycle — fetches on org context change, resets on logout
- Added router navigation guard checking route meta.permission for permission-based route protection
- Added UI permission guards to create buttons on TasksPage, ProcessDefinitionsPage, and EmployeesPage invite button

## Task Commits

Each task was committed atomically:

1. **Task 1: Create permission API client, types, PermissionsPage with tabs, and ProcessAccessDialog** - `d241b02` (feat)
2. **Task 2: Wire usePermissionStore into app lifecycle and add UI permission guards** - `2b3808f` (feat)

## Files Created/Modified
- `frontend/src/modules/organization/api/permission.api.ts` - API client for all permission CRUD endpoints
- `frontend/src/modules/organization/pages/PermissionsPage.vue` - Main permissions page with 3 tabs
- `frontend/src/modules/organization/components/RolePermissionsTab.vue` - Permission matrix grid for roles
- `frontend/src/modules/organization/components/DepartmentPermissionsTab.vue` - Department default permissions matrix
- `frontend/src/modules/organization/components/UserOverridesTab.vue` - User override CRUD with allow/deny tags
- `frontend/src/modules/organization/components/EffectivePermissionsView.vue` - Merged effective permissions table
- `frontend/src/modules/organization/components/ProcessAccessDialog.vue` - Per-definition access control dialog
- `frontend/src/modules/organization/types/organization.types.ts` - Added audit/workflow to PermissionResource, new DTOs
- `frontend/src/modules/organization/stores/permission.store.ts` - Added loaded computed
- `frontend/src/shared/layouts/DashboardLayout.vue` - Permission store lifecycle wiring
- `frontend/src/shared/components/AppSidebar.vue` - Permissions sidebar link with permission gating
- `frontend/src/router/index.ts` - Permission route and navigation guard
- `frontend/src/i18n/locales/en.json` - Permission UI i18n keys
- `frontend/src/i18n/locales/uk.json` - Permission UI i18n keys (Ukrainian)
- `frontend/src/modules/organization/pages/EmployeesPage.vue` - Invite button permission guard
- `frontend/src/modules/tasks/pages/TasksPage.vue` - Create task button permission guard
- `frontend/src/modules/workflow/pages/ProcessDefinitionsPage.vue` - Create process button permission guard
- `frontend/src/modules/organization/pages/OrganizationDetailPage.vue` - Added permissions route matching

## Decisions Made
- Permission matrix uses resource rows x action columns with scope Select dropdown per cell; diff-based save via grant/revoke API
- Router navigation guard allows navigation when permissions not yet loaded (loaded=false) to avoid blocking first render
- Permission store fetch triggered by route.params.orgId watcher in DashboardLayout with immediate:true
- Sidebar permissions link visibility gated by permissionStore.can('role', 'view') || permissionStore.isOwner

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Phase 13 (Granular Permissions RBAC) is now complete
- All 4 plans executed: hierarchy foundation, per-definition access control, management API, and frontend UI
- Permission system operational end-to-end: backend entities + resolver + API + frontend admin UI + permission-gated elements

---
*Phase: 13-granular-permissions-rbac*
*Completed: 2026-03-06*
