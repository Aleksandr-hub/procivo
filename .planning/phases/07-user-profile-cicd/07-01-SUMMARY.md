---
phase: 07-user-profile-cicd
plan: 01
subsystem: auth
tags: [s3, avatar, profile, symfony, doctrine, flysystem, finfo, presigned-url]

# Dependency graph
requires:
  - phase: 01-backend-foundation
    provides: User entity, Identity bounded context, UserRepositoryInterface
  - phase: 03-completion-claim-apis
    provides: S3FileStorage pattern (TaskManager) used as reference implementation

provides:
  - PUT /api/v1/auth/me — profile update endpoint (firstName, lastName, email with uniqueness check)
  - POST /api/v1/auth/me/avatar — avatar upload endpoint (finfo_buffer MIME validation, 5MB limit, S3 storage)
  - GET /api/v1/auth/me returns UserDTO with avatarUrl (24h presigned S3 link or null)
  - AvatarStorageInterface port in Identity bounded context
  - S3AvatarStorage implementation with 24h presigned URL TTL
  - avatar_path column on identity_users table

affects:
  - 07-02-user-profile-frontend
  - future audit phase (profile update events)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - AvatarStorageInterface port mirrors FileStorageInterface in TaskManager (bounded context isolation)
    - finfo_buffer() for server-side MIME validation (not client-reported Content-Type)
    - 24h presigned URL TTL for avatar S3 links (vs 1h for generic file storage)
    - Optional avatarUrl parameter in UserDTO.fromEntity() ensures backward compatibility

key-files:
  created:
    - backend/src/Identity/Application/Port/AvatarStorageInterface.php
    - backend/src/Identity/Infrastructure/Storage/S3AvatarStorage.php
    - backend/migrations/Version20260301120000.php
    - backend/src/Identity/Application/Command/UpdateProfile/UpdateProfileCommand.php
    - backend/src/Identity/Application/Command/UpdateProfile/UpdateProfileHandler.php
    - backend/src/Identity/Application/Command/UploadAvatar/UploadAvatarCommand.php
    - backend/src/Identity/Application/Command/UploadAvatar/UploadAvatarHandler.php
  modified:
    - backend/src/Identity/Domain/Entity/User.php
    - backend/src/Identity/Infrastructure/Persistence/Doctrine/Mapping/User.orm.xml
    - backend/src/Identity/Application/DTO/UserDTO.php
    - backend/src/Identity/Application/Query/GetCurrentUser/GetCurrentUserHandler.php
    - backend/src/Identity/Presentation/Controller/AuthController.php
    - backend/config/services.yaml

key-decisions:
  - "AvatarStorageInterface is Identity's own port — NOT re-using TaskManager FileStorageInterface (bounded context isolation)"
  - "finfo_buffer() used for MIME validation — client-provided MIME type is untrusted"
  - "S3AvatarStorage uses 24h presigned URL TTL (vs 1h in S3FileStorage) for better UX with avatars"
  - "UserDTO.fromEntity() accepts optional ?string $avatarUrl = null parameter — all existing callers unaffected"
  - "Email uniqueness checked only when new email differs from current (avoids false 409 on same-email PUT)"

patterns-established:
  - "Port pattern: AvatarStorageInterface in Application/Port, S3AvatarStorage in Infrastructure/Storage"
  - "Handler validates domain invariants (email uniqueness) before calling entity methods"
  - "UploadAvatarHandler: validate size first (strlen), then MIME (finfo_buffer), then derive extension from map"

requirements-completed: [PROF-01, PROF-02, PROF-04]

# Metrics
duration: 35min
completed: 2026-03-01
---

# Phase 7 Plan 01: User Profile Backend API Summary

**PUT /me profile update + POST /me/avatar S3 upload with finfo_buffer MIME validation and 24h presigned URL returned in GET /me UserDTO**

## Performance

- **Duration:** 35 min
- **Started:** 2026-03-01T17:20:00Z
- **Completed:** 2026-03-01T17:56:29Z
- **Tasks:** 2
- **Files modified:** 13

## Accomplishments

- User entity extended with avatarPath field, updateProfile/setAvatarPath methods, and ORM mapping
- AvatarStorageInterface port created in Identity bounded context (isolated from TaskManager per DDD)
- S3AvatarStorage implementation with 24h presigned URL TTL (longer than generic 1h file storage)
- Migration Version20260301120000 adds avatar_path column to identity_users (applied successfully)
- UpdateProfile command/handler with email uniqueness check returning 409 on conflict
- UploadAvatar command/handler with finfo_buffer server-side MIME validation and 5MB size limit
- UserDTO extended with optional avatarUrl field (null-safe, all existing callers unaffected)
- GET /me enhanced to generate presigned S3 URL when avatarPath exists
- PHPStan Identity module: 0 errors

## Task Commits

Each task was committed atomically:

1. **Task 1: Domain + Infrastructure — User entity, AvatarStorage port, S3 implementation, migration** - `723256b` (feat)
2. **Task 2: Application layer — Commands, handlers, UserDTO extension, controller endpoints** - `ab621a6` (feat, included in docs commit)

## Files Created/Modified

- `backend/src/Identity/Domain/Entity/User.php` - Added avatarPath field, updateProfile(), setAvatarPath(), avatarPath() methods
- `backend/src/Identity/Infrastructure/Persistence/Doctrine/Mapping/User.orm.xml` - Added avatarPath ORM field mapping
- `backend/migrations/Version20260301120000.php` - Migration: ALTER TABLE identity_users ADD COLUMN avatar_path VARCHAR(500)
- `backend/src/Identity/Application/Port/AvatarStorageInterface.php` - Port: upload/getUrl/delete interface for Identity
- `backend/src/Identity/Infrastructure/Storage/S3AvatarStorage.php` - S3 implementation with 24h presigned URL TTL
- `backend/src/Identity/Application/Command/UpdateProfile/UpdateProfileCommand.php` - Command: userId, firstName, lastName, email
- `backend/src/Identity/Application/Command/UpdateProfile/UpdateProfileHandler.php` - Handler with email uniqueness check
- `backend/src/Identity/Application/Command/UploadAvatar/UploadAvatarCommand.php` - Command: userId, fileContent, originalName
- `backend/src/Identity/Application/Command/UploadAvatar/UploadAvatarHandler.php` - Handler with finfo_buffer validation, 5MB limit
- `backend/src/Identity/Application/DTO/UserDTO.php` - Added optional avatarUrl field to constructor and fromEntity()
- `backend/src/Identity/Application/Query/GetCurrentUser/GetCurrentUserHandler.php` - Injects AvatarStorageInterface, generates presigned URL
- `backend/src/Identity/Presentation/Controller/AuthController.php` - PUT /me and POST /me/avatar endpoints added
- `backend/config/services.yaml` - AvatarStorageInterface alias + S3AvatarStorage wired with AWS env vars

## Decisions Made

- AvatarStorageInterface is Identity's own port — NOT importing from TaskManager (bounded context isolation)
- finfo_buffer() for MIME validation — client-reported Content-Type is untrusted (security requirement)
- 24h presigned URL TTL for avatars (better UX — avatar URLs cached in browser longer)
- UserDTO.fromEntity() accepts optional `?string $avatarUrl = null` — backward-compatible, all existing callers work unchanged
- Email uniqueness only checked when new email differs from current (prevents false 409 on no-change PUT)

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None — no external service configuration required beyond existing AWS env vars already in .env.

## Next Phase Readiness

- All backend endpoints ready for Plan 02 (frontend ProfilePage) consumption
- PUT /api/v1/auth/me, POST /api/v1/auth/me/avatar, GET /api/v1/auth/me fully implemented
- AvatarStorageInterface + S3AvatarStorage wired in services.yaml
- No blockers for frontend integration

---
*Phase: 07-user-profile-cicd*
*Completed: 2026-03-01*
