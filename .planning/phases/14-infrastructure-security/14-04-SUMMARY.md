---
phase: 14-infrastructure-security
plan: 04
subsystem: auth
tags: [totp, 2fa, two-factor, qr-code, backup-codes, jwt-partial, remember-device, rate-limiting]

requires:
  - phase: 14-infrastructure-security
    provides: "Soft delete on User entity, security headers"
provides:
  - "TOTP two-factor authentication enrollment (QR code + backup codes)"
  - "Two-step JWT login flow (partial JWT -> TOTP verify -> full JWT)"
  - "Remember device cookie (30-day, HMAC-signed, httpOnly)"
  - "Rate-limited 2FA verification (5 attempts per partial token)"
  - "Backup code recovery (8 single-use codes)"
  - "2FA disable flow with TOTP confirmation"
affects: [identity, auth, frontend-auth]

tech-stack:
  added: [spomky-labs/otphp, endroid/qr-code]
  patterns: [partial-jwt-2fa, aes-256-cbc-secret-encryption, hmac-signed-device-token, cache-based-rate-limiting]

key-files:
  created:
    - backend/src/Identity/Domain/ValueObject/TotpSecret.php
    - backend/src/Identity/Domain/Event/TwoFactorEnabledEvent.php
    - backend/src/Identity/Domain/Event/TwoFactorDisabledEvent.php
    - backend/src/Identity/Application/Port/TotpServiceInterface.php
    - backend/src/Identity/Infrastructure/Security/TotpService.php
    - backend/src/Identity/Infrastructure/Security/RememberDeviceService.php
    - backend/src/Identity/Application/DTO/TwoFactorChallengeDTO.php
    - backend/src/Identity/Application/DTO/TwoFactorSetupDTO.php
    - backend/src/Identity/Application/Command/EnableTwoFactor/EnableTwoFactorCommand.php
    - backend/src/Identity/Application/Command/EnableTwoFactor/EnableTwoFactorHandler.php
    - backend/src/Identity/Application/Command/ConfirmTwoFactor/ConfirmTwoFactorCommand.php
    - backend/src/Identity/Application/Command/ConfirmTwoFactor/ConfirmTwoFactorHandler.php
    - backend/src/Identity/Application/Command/DisableTwoFactor/DisableTwoFactorCommand.php
    - backend/src/Identity/Application/Command/DisableTwoFactor/DisableTwoFactorHandler.php
    - backend/src/Identity/Presentation/Controller/TwoFactorController.php
    - backend/migrations/Version20260307200000.php
  modified:
    - backend/src/Identity/Domain/Entity/User.php
    - backend/src/Identity/Infrastructure/Persistence/Doctrine/Mapping/User.orm.xml
    - backend/src/Identity/Application/Port/JwtTokenManagerInterface.php
    - backend/src/Identity/Infrastructure/Security/LexikJwtTokenManager.php
    - backend/src/Identity/Application/Query/Login/LoginHandler.php
    - backend/src/Identity/Application/Query/Login/LoginQuery.php
    - backend/src/Identity/Presentation/Controller/AuthController.php
    - backend/config/packages/security.yaml
    - backend/composer.json
    - backend/composer.lock

key-decisions:
  - "Partial JWT with empty roles[] and 2fa_required claim for 2FA challenge — cannot access any protected endpoint"
  - "Two-step enrollment: setup saves secret (totpEnabled=false) -> confirm verifies code then enables"
  - "AES-256-CBC encryption for TOTP secret storage using APP_SECRET-derived key"
  - "Backup codes: 8 random hex codes, bcrypt-hashed, single-use consumption"
  - "Remember device: HMAC-signed token with userId + UA hash + expiry, 30-day httpOnly cookie"
  - "Rate limiting via Symfony CacheInterface (Redis-backed) keyed by token hash, 5 attempts max"
  - "Endroid QR Code v6 Builder constructor pattern (not fluent create())"

patterns-established:
  - "Partial JWT pattern: createPartial() returns role-less token with custom claim for controlled endpoint access"
  - "Two-step enrollment: setup command stores secret without enabling -> confirm command verifies and enables"
  - "HMAC-signed device token: base64(payload|signature) with APP_SECRET for stateless remember-me"

requirements-completed: [INFRA-02]

duration: 6min
completed: 2026-03-06
---

# Phase 14 Plan 04: Two-Factor Authentication Summary

**TOTP 2FA with two-step JWT login flow, QR code enrollment, 8 backup codes, and 30-day remember device cookie**

## Performance

- **Duration:** 6 min
- **Started:** 2026-03-06T12:10:59Z
- **Completed:** 2026-03-06T12:16:58Z
- **Tasks:** 2
- **Files modified:** 26

## Accomplishments
- TOTP infrastructure: TotpService with spomky-labs/otphp for code generation/verification, endroid/qr-code for SVG QR
- User entity extended with totpSecret (AES-256-CBC encrypted), totpEnabled flag, backupCodes (bcrypt-hashed JSON array)
- Two-step login flow: password auth returns partial JWT (5min TTL, no roles) when 2FA enabled, then /2fa/verify issues full JWT
- Four 2FA endpoints: setup (QR + backup codes), confirm (verify enrollment), verify (login completion), disable (with code confirmation)
- Remember device cookie: HMAC-signed httpOnly cookie skips 2FA for 30 days on known devices
- Rate limiting: 5 attempts per partial token via Redis-backed cache, prevents brute force on TOTP codes
- Backup code recovery: 8 single-use codes, bcrypt-verified, consumed on successful use

## Task Commits

Each task was committed atomically:

1. **Task 1: TOTP infrastructure -- User entity, TotpService, migration** - `01055df` (feat)
2. **Task 2: Two-step login flow, 2FA endpoints, and rate limiting** - `f195295` (feat)

## Files Created/Modified
- `backend/src/Identity/Domain/ValueObject/TotpSecret.php` - TOTP secret value object
- `backend/src/Identity/Domain/Event/TwoFactorEnabledEvent.php` - Domain event for 2FA enablement
- `backend/src/Identity/Domain/Event/TwoFactorDisabledEvent.php` - Domain event for 2FA disablement
- `backend/src/Identity/Application/Port/TotpServiceInterface.php` - Port for TOTP operations
- `backend/src/Identity/Infrastructure/Security/TotpService.php` - TOTP generation, verification, QR, backup codes, encryption
- `backend/src/Identity/Infrastructure/Security/RememberDeviceService.php` - HMAC-signed device cookie management
- `backend/src/Identity/Application/DTO/TwoFactorChallengeDTO.php` - Login response when 2FA required
- `backend/src/Identity/Application/DTO/TwoFactorSetupDTO.php` - Enrollment response with QR + backup codes
- `backend/src/Identity/Application/Command/EnableTwoFactor/` - Setup command (stores secret, not yet enabled)
- `backend/src/Identity/Application/Command/ConfirmTwoFactor/` - Confirm enrollment command
- `backend/src/Identity/Application/Command/DisableTwoFactor/` - Disable 2FA command
- `backend/src/Identity/Presentation/Controller/TwoFactorController.php` - 4 endpoints: setup, confirm, verify, disable
- `backend/migrations/Version20260307200000.php` - Add TOTP columns to identity_users
- `backend/src/Identity/Domain/Entity/User.php` - Added TOTP fields and methods
- `backend/src/Identity/Application/Query/Login/LoginHandler.php` - Two-step flow with remember device check
- `backend/src/Identity/Application/Port/JwtTokenManagerInterface.php` - Added createPartial()
- `backend/src/Identity/Infrastructure/Security/LexikJwtTokenManager.php` - Implemented createPartial()
- `backend/config/packages/security.yaml` - Added /2fa/verify as PUBLIC_ACCESS

## Decisions Made
- Partial JWT uses empty roles array (not a special role) — simplest way to prevent access to any ROLE_USER endpoint
- Two-step enrollment prevents 2FA lock-out: user must prove they can generate valid code before 2FA activates
- Endroid QR Code v6 uses constructor-based Builder (not fluent ::create() from v5)
- Cache-based rate limiting (not Symfony rate limiter) because keying by partial token hash, not IP
- /2fa/verify is PUBLIC_ACCESS because partial JWT has no roles and fails standard JWT firewall validation

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Endroid QR Code v6 API change**
- **Found during:** Task 1 (TotpService implementation)
- **Issue:** Plan specified `Builder::create()->writer()->data()` fluent API, but endroid/qr-code v6 uses constructor-based pattern
- **Fix:** Changed to `new Builder(writer: new SvgWriter(), data: $uri, size: 300, margin: 10)` then `->build()`
- **Files modified:** backend/src/Identity/Infrastructure/Security/TotpService.php
- **Verification:** PHPStan level 6 passes
- **Committed in:** 01055df (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** API compatibility fix for newer library version. No scope creep.

## Issues Encountered
None beyond the library API change noted above.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- 2FA backend complete, ready for frontend integration (login flow, settings page)
- Phase 14-05 (remaining plan) can proceed independently

## Self-Check: PASSED

- All 16 created files verified on disk
- Commit 01055df (Task 1) verified in git log
- Commit f195295 (Task 2) verified in git log

---
*Phase: 14-infrastructure-security*
*Completed: 2026-03-06*
