---
phase: 12-super-admin-impersonation
plan: 01
subsystem: auth
tags: [jwt, impersonation, rbac, audit, symfony-console]

requires:
  - phase: 08-audit-logging
    provides: AuditLog entity, AuditLogRepositoryInterface, event handler pattern
  - phase: 07-user-profile-cicd
    provides: JwtTokenManagerInterface, LexikJwtTokenManager, SecurityUser
provides:
  - POST /api/v1/admin/impersonate/{userId} endpoint with short-lived JWT
  - POST /api/v1/admin/impersonate/end endpoint
  - ImpersonationStarted/EndedEvent domain events with audit handlers
  - ROLE_SUPER_ADMIN hierarchy and access control
  - app:promote-user console command
  - JwtTokenManagerInterface::createImpersonation() with impersonated_by claim
affects: [12-02-frontend-impersonation]

tech-stack:
  added: [JWTEncoderInterface for custom JWT payloads]
  patterns: [JWT payload inspection for chained impersonation detection, query bus for data-returning operations]

key-files:
  created:
    - backend/src/Identity/Application/Query/ImpersonateUser/ImpersonateUserQuery.php
    - backend/src/Identity/Application/Query/ImpersonateUser/ImpersonateUserHandler.php
    - backend/src/Identity/Application/Command/EndImpersonation/EndImpersonationCommand.php
    - backend/src/Identity/Application/Command/EndImpersonation/EndImpersonationHandler.php
    - backend/src/Identity/Application/DTO/ImpersonationDTO.php
    - backend/src/Identity/Domain/Event/ImpersonationStartedEvent.php
    - backend/src/Identity/Domain/Event/ImpersonationEndedEvent.php
    - backend/src/Identity/Domain/Exception/ImpersonationNotAllowedException.php
    - backend/src/Identity/Presentation/Controller/AdminController.php
    - backend/src/Audit/Application/EventHandler/OnImpersonationStartedAudit.php
    - backend/src/Audit/Application/EventHandler/OnImpersonationEndedAudit.php
    - backend/src/Identity/Application/Command/PromoteUser/PromoteUserCommand.php
    - backend/src/Identity/Application/Command/PromoteUser/PromoteUserHandler.php
    - backend/src/Identity/Presentation/Console/PromoteUserConsoleCommand.php
  modified:
    - backend/src/Identity/Application/Port/JwtTokenManagerInterface.php
    - backend/src/Identity/Infrastructure/Security/LexikJwtTokenManager.php
    - backend/src/Identity/Domain/Entity/User.php
    - backend/config/packages/security.yaml

key-decisions:
  - "ImpersonateUser uses query.bus (not command.bus) because it needs to return ImpersonationDTO — command bus dispatch() returns void"
  - "JWT impersonation uses JWTEncoderInterface::encode() directly for custom 900s TTL — jwtManager->create() always uses configured 3600s TTL"
  - "Chained impersonation detection via base64 JWT payload parsing in controller — no SecurityUser extension needed"

patterns-established:
  - "Admin controller pattern: assertSuperAdmin() guard method, /api/v1/admin route prefix"
  - "Impersonation JWT: impersonated_by claim identifies admin, 15-min TTL, no refresh token"

requirements-completed: [ADMN-01]

duration: 5min
completed: 2026-03-05
---

# Phase 12 Plan 01: Backend Impersonation Infrastructure Summary

**Short-lived JWT impersonation with impersonated_by claim, privilege guards, audit trail, and promote-user CLI**

## Performance

- **Duration:** 5 min
- **Started:** 2026-03-05T21:40:46Z
- **Completed:** 2026-03-05T21:45:21Z
- **Tasks:** 2
- **Files modified:** 18

## Accomplishments
- Extended JwtTokenManagerInterface with createImpersonation() producing 15-minute JWTs with impersonated_by claim
- AdminController with impersonate/end endpoints, privilege escalation guards (cannot impersonate super admin, cannot chain impersonation)
- Full audit trail via ImpersonationStarted/EndedEvent and corresponding audit handlers
- ROLE_SUPER_ADMIN role hierarchy and /api/v1/admin access control
- app:promote-user console command for assigning ROLE_SUPER_ADMIN

## Task Commits

Each task was committed atomically:

1. **Task 1: JWT extension + Domain events + ImpersonateUser/EndImpersonation handlers + ImpersonationDTO** - `f479159` (feat)
2. **Task 2: AdminController + audit handlers + security config + PromoteUser console command** - `b72997b` (feat)

## Files Created/Modified
- `backend/src/Identity/Application/Port/JwtTokenManagerInterface.php` - Added createImpersonation() method
- `backend/src/Identity/Infrastructure/Security/LexikJwtTokenManager.php` - Implemented via JWTEncoderInterface::encode()
- `backend/src/Identity/Application/Query/ImpersonateUser/ImpersonateUserQuery.php` - Query with admin/target IDs, reason, impersonation flag
- `backend/src/Identity/Application/Query/ImpersonateUser/ImpersonateUserHandler.php` - Validates privileges, creates impersonation JWT, dispatches event
- `backend/src/Identity/Application/Command/EndImpersonation/EndImpersonationCommand.php` - Command with adminUserId
- `backend/src/Identity/Application/Command/EndImpersonation/EndImpersonationHandler.php` - Dispatches ImpersonationEndedEvent
- `backend/src/Identity/Application/DTO/ImpersonationDTO.php` - JsonSerializable with access_token, impersonated_user, expires_in
- `backend/src/Identity/Domain/Event/ImpersonationStartedEvent.php` - Domain event with admin/target/reason
- `backend/src/Identity/Domain/Event/ImpersonationEndedEvent.php` - Domain event with adminUserId
- `backend/src/Identity/Domain/Exception/ImpersonationNotAllowedException.php` - Static factories: userNotFound, cannotImpersonateSuperAdmin, alreadyImpersonating
- `backend/src/Identity/Presentation/Controller/AdminController.php` - POST /impersonate/{userId} and POST /impersonate/end
- `backend/src/Audit/Application/EventHandler/OnImpersonationStartedAudit.php` - Records impersonation.started audit entry
- `backend/src/Audit/Application/EventHandler/OnImpersonationEndedAudit.php` - Records impersonation.ended audit entry
- `backend/config/packages/security.yaml` - ROLE_SUPER_ADMIN hierarchy + /api/v1/admin access control
- `backend/src/Identity/Domain/Entity/User.php` - Added addRole() method
- `backend/src/Identity/Application/Command/PromoteUser/PromoteUserCommand.php` - Command with email and role
- `backend/src/Identity/Application/Command/PromoteUser/PromoteUserHandler.php` - Finds user by email, adds role, saves
- `backend/src/Identity/Presentation/Console/PromoteUserConsoleCommand.php` - app:promote-user with email arg and --role option

## Decisions Made
- **Query bus for impersonation:** Used query.bus instead of command.bus for ImpersonateUserHandler because it returns ImpersonationDTO — command bus dispatch() returns void by design
- **JWTEncoderInterface for custom TTL:** Used encode() directly instead of jwtManager->create() to control the 900s expiration and add impersonated_by claim
- **JWT payload inspection:** Detecting chained impersonation by parsing the Authorization header JWT payload in the controller — simpler than extending SecurityUser

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] ImpersonateUser moved from command.bus to query.bus**
- **Found during:** Task 1 (ImpersonateUserHandler implementation)
- **Issue:** Plan specified command.bus for ImpersonateUserHandler, but CommandBusInterface::dispatch() returns void — handler needs to return ImpersonationDTO to controller
- **Fix:** Created ImpersonateUserQuery/Handler using query.bus pattern (like LoginQuery) instead of command pattern
- **Files modified:** Created in Application/Query/ImpersonateUser/ instead of Application/Command/ImpersonateUser/
- **Verification:** Container compiles, PHPStan level 5 passes
- **Committed in:** f479159 (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** Essential for functionality — command bus cannot return values. Query bus is the correct pattern for this use case.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Backend impersonation API complete, ready for frontend integration (Plan 02)
- app:promote-user available to create test super admin users
- Impersonation events will be routed sync by default (no messenger routing added) — sufficient for now

## Self-Check: PASSED

All 16 created files verified on disk. Both task commits (f479159, b72997b) verified in git log.

---
*Phase: 12-super-admin-impersonation*
*Completed: 2026-03-05*
