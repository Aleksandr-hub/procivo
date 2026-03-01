---
phase: 09-notification-system
plan: 02
subsystem: ui
tags: [notifications, mercure, sse, vue3, pinia, primevue, i18n, eventSource]

# Dependency graph
requires:
  - phase: 09-notification-system plan 01
    provides: NotificationController with preferences API, Mercure SSE publisher to /users/{id}/notifications, NotificationDTO with channel/readAt/relatedEntityType

provides:
  - Mercure SSE real-time subscription in notification.store (initMercure/closeMercure) replacing polling
  - NotificationsPage.vue — full notification center with type filter, mark-all-read, click-to-navigate
  - NotificationPreferences interface and API client methods (getPreferences/savePreferences)
  - Preferences toggle matrix (7 event types x 2 channels) in ProfilePage
  - Notifications global nav link in AppSidebar
  - Complete i18n keys for notifications.center, notifications.preferences, notifications.types (en + uk)

affects:
  - future-phases-using-notifications
  - phase-10-and-beyond (UI patterns for real-time stores)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - DashboardLayout watches authStore.user with immediate:true to call initMercure/closeMercure lifecycle
    - Mercure SSE via native EventSource (not polling) — single EventSource per user session
    - getPreference/setPreference helpers abstract localPreferences deep-clone pattern
    - useTemplateRef avoided in favor of plain ref() for Popover component compatibility

key-files:
  created:
    - frontend/src/modules/notifications/pages/NotificationsPage.vue
  modified:
    - frontend/src/modules/notifications/types/notification.types.ts
    - frontend/src/modules/notifications/api/notification.api.ts
    - frontend/src/modules/notifications/stores/notification.store.ts
    - frontend/src/modules/notifications/components/NotificationBell.vue
    - frontend/src/shared/layouts/DashboardLayout.vue
    - frontend/src/router/index.ts
    - frontend/src/modules/auth/pages/ProfilePage.vue
    - frontend/src/shared/components/AppSidebar.vue
    - frontend/src/i18n/locales/en.json
    - frontend/src/i18n/locales/uk.json

key-decisions:
  - "DashboardLayout uses watch(authStore.user, { immediate: true }) to init/close Mercure SSE — single responsibility, works on refresh and login/logout"
  - "NotificationBell polling removed — Mercure SSE is sole real-time source; initial unreadCount fetched once on mount"
  - "NotificationsPage navigation uses currentOrgId from organization.store for task/process_instance links — simplest approach when user is in org context"
  - "getPreference/setPreference helpers with deep-clone on mount — avoids reactive aliasing issue with nested preferences object"

requirements-completed: [NOTF-02, NOTF-04, NOTF-05, NOTF-06]

# Metrics
duration: 3min
completed: 2026-03-01
---

# Phase 09 Plan 02: Notification Frontend Summary

**Mercure SSE real-time bell + NotificationsPage (type filter, click-to-navigate) + preferences toggle matrix in ProfilePage replacing notification polling**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-01T19:25:13Z
- **Completed:** 2026-03-01T19:28:00Z
- **Tasks:** 2
- **Files modified:** 10

## Accomplishments

- Replaced polling in NotificationBell with Mercure SSE via native EventSource — DashboardLayout manages lifecycle (initMercure on login, closeMercure on logout) by watching authStore.user
- Created NotificationsPage with type filter Select, mark-all-read button, click-to-navigate for task/process_instance/organization entities, empty state
- Added notification preferences section to ProfilePage with 7-event-type x 2-channel ToggleSwitch matrix and save-to-API with toast

## Task Commits

1. **Task 1: Extend types/API/store with Mercure SSE and NotificationsPage** - `c367d24` (feat)
2. **Task 2: Preferences UI in ProfilePage, sidebar nav link, i18n keys** - `6ad51fd` (feat)

## Files Created/Modified

- `frontend/src/modules/notifications/types/notification.types.ts` - Extended NotificationDTO with relatedEntityType/channel/readAt; 8 NotificationType values; NotificationPreferences interface
- `frontend/src/modules/notifications/api/notification.api.ts` - Added optional type filter to list(); getPreferences/savePreferences methods
- `frontend/src/modules/notifications/stores/notification.store.ts` - Mercure SSE via EventSource (initMercure/closeMercure); preferences state; typeFilter ref
- `frontend/src/modules/notifications/components/NotificationBell.vue` - Removed polling setInterval; added 'View all' footer link; extended getTypeIcon for all 8 types
- `frontend/src/modules/notifications/pages/NotificationsPage.vue` - New: type filter, notification list, click-to-navigate, mark-all-read
- `frontend/src/shared/layouts/DashboardLayout.vue` - Added watch on authStore.user to call initMercure/closeMercure
- `frontend/src/router/index.ts` - /notifications route added after /profile
- `frontend/src/modules/auth/pages/ProfilePage.vue` - Notification preferences section with ToggleSwitch matrix, getPreference/setPreference helpers, save button
- `frontend/src/shared/components/AppSidebar.vue` - Notifications nav link (pi-bell) added as global item
- `frontend/src/i18n/locales/en.json` - notifications.center, notifications.preferences, notifications.types, notifications.sidebar keys
- `frontend/src/i18n/locales/uk.json` - Same keys with Ukrainian translations

## Decisions Made

- **DashboardLayout manages Mercure lifecycle:** Used `watch(authStore.user, { immediate: true })` — single place, handles page refresh (user already set), login, and logout. No extra composable needed.
- **Polling removed completely:** NotificationBell no longer uses setInterval. Mercure SSE is the only real-time source, consistent with plan's "no polling dependency" requirement.
- **Navigation uses currentOrgId:** NotificationsPage navigates to task/process using `orgStore.currentOrgId`. Notifications don't carry orgId, so this is the simplest approach when user is in an org context.
- **getPreference/setPreference helpers:** Deep clone on mount (JSON.parse/stringify) avoids reactive aliasing. Helpers default in_app=true/email=false for missing event types (matches backend defaults).

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Full notification frontend complete: bell icon, SSE real-time, notification center, preferences
- Backend API (09-01) + Frontend (09-02) together deliver complete notification system
- Phase 09 is now complete — ready to proceed to Phase 10 (Search/Elasticsearch) or Phase 11 (Timer-based processes)

## Self-Check: PASSED

All files confirmed present. Commits c367d24 and 6ad51fd verified in git log.

---
*Phase: 09-notification-system*
*Completed: 2026-03-01*
