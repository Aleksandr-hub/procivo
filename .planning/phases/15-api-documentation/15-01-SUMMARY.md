---
phase: 15-api-documentation
plan: 01
subsystem: api
tags: [openapi, swagger, nelmio, dto-schema, swagger-ui]

requires:
  - phase: 14-infrastructure-security
    provides: security.yaml with access_control for /api/doc routes
provides:
  - NelmioApiDocBundle registered and configured with JWT Bearer security scheme
  - Swagger UI at /api/docs and JSON spec at /api/doc.json
  - All 38 DTO classes annotated with OA\Schema and OA\Property attributes
affects: [15-api-documentation plans 02 and 03]

tech-stack:
  added: [nelmio/api-doc-bundle v5.9.4, symfony/asset v8.0.6, zircote/swagger-php]
  patterns: [OA\Schema + OA\Property attributes on readonly DTO classes]

key-files:
  created:
    - backend/config/packages/nelmio_api_doc.yaml
    - backend/config/routes/nelmio_api_doc.yaml
  modified:
    - backend/config/bundles.php
    - backend/src/*/Application/DTO/*.php (38 files)

key-decisions:
  - "symfony/asset required for Swagger UI controller — NelmioApiDocBundle removes swagger_ui service when Asset component missing"
  - "DTO schemas not visible in spec until controllers reference them — expected behavior, Plan 02 will add controller annotations"

patterns-established:
  - "OA\\Schema on class + OA\\Property on each promoted constructor param for all DTOs"
  - "Use format: uuid/date-time/email/uri, enum for status fields, nullable for optional params"
  - "Array properties use items with $ref for nested DTOs or OA\\Items for primitive arrays"

requirements-completed: [DOCS-02]

duration: 9min
completed: 2026-03-06
---

# Phase 15 Plan 01: NelmioApiDocBundle Setup and DTO Schema Annotations Summary

**Swagger UI at /api/docs with NelmioApiDocBundle, JWT Bearer security scheme, and all 38 DTOs annotated with OpenAPI Schema/Property attributes**

## Performance

- **Duration:** 9 min
- **Started:** 2026-03-06T16:05:04Z
- **Completed:** 2026-03-06T16:14:04Z
- **Tasks:** 2
- **Files modified:** 43

## Accomplishments
- NelmioApiDocBundle registered and configured with JWT Bearer security scheme, server info, and CDN assets
- Swagger UI serving at /api/docs, JSON spec at /api/doc.json (96 paths discovered from existing controllers)
- All 38 DTO classes across 6 modules annotated with OA\Schema and OA\Property attributes including formats, enums, nullable flags, and nested $ref items

## Task Commits

Each task was committed atomically:

1. **Task 1: Register NelmioApiDocBundle, create config, add routes** - `07d2524` (chore)
2. **Task 2: Add OA\Schema and OA\Property attributes to all 38 DTOs** - `6a6d08f` (feat)

## Files Created/Modified
- `backend/config/bundles.php` - Added NelmioApiDocBundle registration
- `backend/config/packages/nelmio_api_doc.yaml` - Bundle config with JWT Bearer, servers, area patterns
- `backend/config/routes/nelmio_api_doc.yaml` - Routes for /api/docs and /api/doc.json
- `backend/composer.json` + `backend/composer.lock` - Added symfony/asset dependency
- `backend/src/Audit/Application/DTO/AuditLogDTO.php` - OA attributes for audit log entry
- `backend/src/Identity/Application/DTO/*.php` (5 files) - OA attributes for auth, user, 2FA, impersonation DTOs
- `backend/src/Notification/Application/DTO/*.php` (2 files) - OA attributes for notification DTOs
- `backend/src/Organization/Application/DTO/*.php` (12 files) - OA attributes for org, department, role, permission DTOs
- `backend/src/TaskManager/Application/DTO/*.php` (8 files) - OA attributes for task, board, comment, label DTOs
- `backend/src/Workflow/Application/DTO/*.php` (10 files) - OA attributes for process, node, transition DTOs

## Decisions Made
- symfony/asset v8.0.6 installed as required dependency — NelmioApiDocBundle removes swagger_ui controller service when Symfony Asset component is not present (TwigBundle alone not sufficient)
- DTO schemas will appear in OpenAPI spec only when referenced by controller annotations — this is standard NelmioApiDocBundle behavior, Plan 02 covers controller annotations

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Installed symfony/asset for Swagger UI rendering**
- **Found during:** Task 1 (NelmioApiDocBundle setup)
- **Issue:** Swagger UI returned 500 — controller service `nelmio_api_doc.controller.swagger_ui` not registered because NelmioApiDocBundle requires both TwigBundle AND symfony/asset
- **Fix:** Installed symfony/asset via composer require
- **Files modified:** backend/composer.json, backend/composer.lock
- **Verification:** /api/docs returns 200, Swagger UI renders correctly
- **Committed in:** 07d2524 (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** Essential dependency for Swagger UI rendering. No scope creep.

## Issues Encountered
- DTO schemas show 0 in OpenAPI spec because no controllers reference them yet — this is expected and will be resolved in Plan 02 (controller annotations)

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- NelmioApiDocBundle infrastructure ready for controller annotations (Plan 02)
- All DTO schemas prepared with full OpenAPI metadata for $ref references
- Swagger UI accessible at http://localhost:8080/api/docs

---
*Phase: 15-api-documentation*
*Completed: 2026-03-06*
