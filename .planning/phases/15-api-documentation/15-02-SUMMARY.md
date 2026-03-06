---
phase: 15-api-documentation
plan: 02
subsystem: api
tags: [openapi, swagger, nelmio, controller-annotations, identity, organization]

requires:
  - phase: 15-api-documentation
    provides: NelmioApiDocBundle configured with JWT Bearer security, all 38 DTOs with OA\Schema
provides:
  - OpenAPI annotations on all 15 Identity, Organization, and Shared controllers
  - Tags visible in Swagger UI: Auth, Users, Admin, Health, Metrics, Organizations, Departments, Employees, Invitations, Roles, Positions, Permissions
  - Request/response schemas for every endpoint with DTO $ref links
affects: [15-api-documentation plan 03]

tech-stack:
  added: []
  patterns: [OA\Tag on class, OA\Get/Post/Put/Delete per method, OA\Parameter for path/query params, OA\RequestBody with OA\JsonContent, OA\Response with Model ref or inline schema, Security(name: null) for public endpoints]

key-files:
  created: []
  modified:
    - backend/src/Identity/Presentation/Controller/AuthController.php
    - backend/src/Identity/Presentation/Controller/TwoFactorController.php
    - backend/src/Identity/Presentation/Controller/UserController.php
    - backend/src/Identity/Presentation/Controller/AdminController.php
    - backend/src/Shared/Presentation/Controller/HealthController.php
    - backend/src/Shared/Presentation/Controller/MetricsController.php
    - backend/src/Shared/Presentation/Controller/AdminRestoreController.php
    - backend/src/Organization/Presentation/Controller/OrganizationController.php
    - backend/src/Organization/Presentation/Controller/DepartmentController.php
    - backend/src/Organization/Presentation/Controller/EmployeeController.php
    - backend/src/Organization/Presentation/Controller/InvitationController.php
    - backend/src/Organization/Presentation/Controller/PublicInvitationController.php
    - backend/src/Organization/Presentation/Controller/RoleController.php
    - backend/src/Organization/Presentation/Controller/PositionController.php
    - backend/src/Organization/Presentation/Controller/PermissionController.php

key-decisions:
  - "UserController only has search endpoint (profile/avatar endpoints live in AuthController) — annotated actual structure, not plan assumption"
  - "Public endpoints (register, login, refresh, 2fa/verify, health/*, metrics, public invitations) use Security(name: null) to override default JWT requirement"

patterns-established:
  - "OA\\Tag on controller class, OA method attribute (Get/Post/Put/Delete) with summary on each method"
  - "Path parameters via OA\\Parameter with format: uuid for all entity IDs"
  - "Model(type: SomeDTO::class) ref for response content linking to DTO schemas from Plan 01"
  - "Consistent 401 Unauthorized + 403 Forbidden on all permission-checked endpoints"

requirements-completed: [DOCS-01]

duration: 12min
completed: 2026-03-06
---

# Phase 15 Plan 02: Controller Endpoint Annotations Summary

**OpenAPI annotations on 15 controllers (46+ endpoints) across Identity, Organization, and Shared modules with full request/response schemas and DTO $ref links**

## Performance

- **Duration:** 12 min
- **Started:** 2026-03-06T16:17:00Z
- **Completed:** 2026-03-06T16:29:17Z
- **Tasks:** 2
- **Files modified:** 15

## Accomplishments
- 7 Identity + Shared controllers annotated: AuthController (8 endpoints), TwoFactorController (4 endpoints), UserController (1 endpoint), AdminController (2 endpoints), HealthController (4 endpoints), MetricsController (1 endpoint), AdminRestoreController (1 endpoint)
- 8 Organization controllers annotated: OrganizationController (5), DepartmentController (6), EmployeeController (8), InvitationController (3), PublicInvitationController (2), RoleController (12), PositionController (4), PermissionController (6)
- All 12 expected tags now visible in Swagger UI: Auth, Users, Admin, Health, Metrics, Organizations, Departments, Employees, Invitations, Roles, Positions, Permissions
- 134 total endpoints documented in OpenAPI spec (20 tags including existing TaskManager/Workflow/etc. modules)

## Task Commits

Each task was committed atomically:

1. **Task 1: Annotate Identity + Shared controllers (7 controllers)** - `fd3f25a` (feat)
2. **Task 2: Annotate Organization controllers (8 controllers)** - `53752df` (feat)

## Files Created/Modified
- `backend/src/Identity/Presentation/Controller/AuthController.php` - OA annotations for register, login, refresh, logout, me, updateProfile, uploadAvatar, changePassword
- `backend/src/Identity/Presentation/Controller/TwoFactorController.php` - OA annotations for setup, confirm, verify (public), disable
- `backend/src/Identity/Presentation/Controller/UserController.php` - OA annotations for user search with query params
- `backend/src/Identity/Presentation/Controller/AdminController.php` - OA annotations for impersonate/endImpersonation
- `backend/src/Shared/Presentation/Controller/HealthController.php` - OA annotations for 4 health endpoints (all public)
- `backend/src/Shared/Presentation/Controller/MetricsController.php` - OA annotations for Prometheus metrics (public, text/plain)
- `backend/src/Shared/Presentation/Controller/AdminRestoreController.php` - OA annotations for entity restore
- `backend/src/Organization/Presentation/Controller/OrganizationController.php` - OA annotations for CRUD + suspend
- `backend/src/Organization/Presentation/Controller/DepartmentController.php` - OA annotations for CRUD + tree + move
- `backend/src/Organization/Presentation/Controller/EmployeeController.php` - OA annotations for hire, list, show, update, dismiss, setManager, subordinates, orgChart
- `backend/src/Organization/Presentation/Controller/InvitationController.php` - OA annotations for create, list, cancel
- `backend/src/Organization/Presentation/Controller/PublicInvitationController.php` - OA annotations for show/accept (public)
- `backend/src/Organization/Presentation/Controller/RoleController.php` - OA annotations for CRUD roles, permissions, employee roles, my-permissions
- `backend/src/Organization/Presentation/Controller/PositionController.php` - OA annotations for CRUD positions
- `backend/src/Organization/Presentation/Controller/PermissionController.php` - OA annotations for dept perms, user overrides, effective perms

## Decisions Made
- UserController in code only has a search endpoint; profile update and avatar endpoints are in AuthController under /auth/me — annotated the actual code structure
- Public endpoints use `#[Security(name: null)]` from Nelmio to override the global JWT Bearer security scheme
- Used `Nelmio\ApiDocBundle\Attribute\Model` ref for all DTO response schemas to link to OA\Schema definitions from Plan 01

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All Identity, Organization, and Shared controllers fully documented
- Plan 03 can now annotate remaining modules (TaskManager, Workflow, Audit, Notification, Dashboard)
- Swagger UI at /api/docs shows all 12 planned tags with request/response schemas

---
*Phase: 15-api-documentation*
*Completed: 2026-03-06*
