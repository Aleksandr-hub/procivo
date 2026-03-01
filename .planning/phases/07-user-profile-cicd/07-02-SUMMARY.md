---
phase: 07-user-profile-cicd
plan: 02
subsystem: auth
tags: [vue3, pinia, avatar, profile, primevue, i18n, router]

# Dependency graph
requires:
  - phase: 07-user-profile-cicd
    plan: 01
    provides: PUT /auth/me, POST /auth/me/avatar, GET /auth/me with avatarUrl, UserDTO.avatarUrl

provides:
  - ProfilePage.vue with avatar upload, profile edit form, password change form at /profile
  - auth store updateProfile, uploadAvatar, changePassword actions
  - UserDTO avatarUrl field in frontend types
  - Avatar display in AppTopbar with presigned URL or initials fallback, clickable to /profile
  - Avatar display in TaskDetailSidebar for current-user assignee and creator

affects:
  - future phases that extend user profile (audit, impersonation)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - ProfilePage uses three PrimeVue Card sections (avatar, profile form, password)
    - Avatar component: :image for presigned URL, :label for initials fallback (undefined when image present)
    - Auth store actions use dynamic import of httpClient (consistent with existing pattern)
    - isCurrentUserAssignee/isCurrentUserCreator computed for pragmatic avatar matching without DTO extension

key-files:
  created:
    - frontend/src/modules/auth/pages/ProfilePage.vue
  modified:
    - frontend/src/modules/auth/types/auth.types.ts
    - frontend/src/modules/auth/stores/auth.store.ts
    - frontend/src/router/index.ts
    - frontend/src/shared/components/AppTopbar.vue
    - frontend/src/modules/tasks/components/TaskDetailSidebar.vue
    - frontend/src/i18n/locales/uk.json
    - frontend/src/i18n/locales/en.json

key-decisions:
  - "Avatar image prop is undefined (not null) when no avatar — PrimeVue Avatar treats null as truthy for image slot"
  - "TaskDetailSidebar: show current user's avatar only when task assignee/creator matches auth.user.id — avoids extending task DTOs prematurely"
  - "ProfilePage.vue uses onMounted to initialize form from auth.user (not watch) — user is always populated before page renders"
  - "uploadAvatar and updateProfile both call fetchUser() after success to refresh store including new avatarUrl"

patterns-established:
  - "Avatar with presigned URL: :image='auth.user?.avatarUrl ?? undefined' :label='auth.user?.avatarUrl ? undefined : initials'"
  - "Store actions: dynamic import httpClient, call fetchUser() after mutation to sync server state"

requirements-completed: [PROF-01, PROF-03, PROF-05]

# Metrics
duration: 3min
completed: 2026-03-01
---

# Phase 7 Plan 02: User Profile Frontend Summary

**ProfilePage.vue with avatar upload, profile edit, password change + Avatar display in topbar (clickable to /profile) and TaskDetailSidebar for current user**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-01T17:59:24Z
- **Completed:** 2026-03-01T18:02:06Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments

- UserDTO extended with `avatarUrl?: string` in frontend types and auth store user ref type
- Auth store gains `updateProfile`, `uploadAvatar`, `changePassword` async actions, each exported
- ProfilePage.vue created with three PrimeVue Card sections: avatar upload with hidden file input, profile form (firstName, lastName, email), password change (current + new); success/error toasts throughout
- `/profile` route registered inside DashboardLayout children (before organizations route)
- AppTopbar updated: user name span replaced with Avatar + name div, clicking navigates to /profile; initials computed fallback when no avatarUrl
- TaskDetailSidebar: `isCurrentUserAssignee` and `isCurrentUserCreator` computed properties; Avatar shows current user's presigned URL when matched, falls back to initials otherwise
- 14 i18n keys added to both `uk.json` and `en.json` under `profile.*`
- TypeScript compiles with 0 errors, Vite build passes

## Task Commits

Each task was committed atomically:

1. **Task 1: Auth store + types + ProfilePage + router + i18n** - `467eec6` (feat)
2. **Task 2: Avatar display in topbar and task detail sidebar** - `58f63ba` (feat)

## Files Created/Modified

- `frontend/src/modules/auth/pages/ProfilePage.vue` - New profile page (3 Card sections: avatar upload, profile form, password change)
- `frontend/src/modules/auth/types/auth.types.ts` - Added `avatarUrl?: string` to UserDTO
- `frontend/src/modules/auth/stores/auth.store.ts` - Extended user ref type, added updateProfile/uploadAvatar/changePassword actions
- `frontend/src/router/index.ts` - Added profile route inside DashboardLayout
- `frontend/src/shared/components/AppTopbar.vue` - Replaced user name span with Avatar+name div, initials computed, user-info CSS
- `frontend/src/modules/tasks/components/TaskDetailSidebar.vue` - Added useAuthStore, isCurrentUserAssignee/Creator computed, Avatar image integration
- `frontend/src/i18n/locales/uk.json` - Added 14 profile.* keys in Ukrainian
- `frontend/src/i18n/locales/en.json` - Added 14 profile.* keys in English

## Decisions Made

- `Avatar :image` receives `undefined` (not `null`) when no avatar URL — PrimeVue Avatar treats falsy undefined correctly for the image slot, falling back to `:label`
- TaskDetailSidebar uses current user's avatarUrl only when `task.assigneeId === auth.user.id` — pragmatic approach that avoids extending task/employee DTOs before Phase 8 (Audit) which will add avatarUrl to all user DTOs
- `ProfilePage.vue` initializes form from `auth.user` in `onMounted` — user is always hydrated before the guarded route renders, so no need for a watch
- `uploadAvatar` and `updateProfile` both call `fetchUser()` after success to sync the store with the server's new `avatarUrl` presigned URL

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None — no external service configuration required beyond existing AWS env vars already set up in Phase 07-01.

## Next Phase Readiness

- Profile page fully functional: avatar upload to S3, profile edit, password change
- Avatar shown in topbar and task detail sidebar for the current logged-in user
- Task/employee DTOs still carry initials-only avatars for other users — full avatar support for all users planned for Phase 8 (Audit) when user avatarUrl is added to employee/task read models
- No blockers for Phase 07-03 or Phase 07-04

---
*Phase: 07-user-profile-cicd*
*Completed: 2026-03-01*
