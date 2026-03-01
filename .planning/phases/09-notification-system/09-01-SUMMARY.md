---
phase: 09-notification-system
plan: 01
subsystem: api
tags: [notifications, mercure, sse, email, symfony-mailer, twig, doctrine, cqrs, ddd]

# Dependency graph
requires:
  - phase: 08-audit-logging
    provides: async event infrastructure, Doctrine DBAL patterns
  - phase: 07-user-profile-cicd
    provides: UserRepositoryInterface, User.email() getter
  - phase: 01-identity-organization
    provides: UserRepositoryInterface, InvitationCreatedEvent

provides:
  - NotificationPreference entity with per-user, per-event-type, per-channel enabled/disabled control
  - NotificationDispatcher service centralizing preference check, DB save, Mercure publish, email send
  - NotificationMercurePublisher publishing to /users/{recipientId}/notifications topics
  - SymfonyNotificationMailer with 7 Twig email templates
  - 7 event handlers covering all notification triggers (task, process, comment, invitation)
  - Preferences CRUD API: GET returns map with defaults, PUT saves bulk updates
  - List notifications API with optional ?type= filter
  - DB migration: channel, related_entity_type, read_at columns + notification_preferences table

affects:
  - 09-02-notification-frontend
  - future-phases-using-notifications

# Tech tracking
tech-stack:
  added: []
  patterns:
    - NotificationDispatcher as central preference-aware dispatch hub
    - DBAL queries in sync handlers (OnProcessCompleted) to avoid Doctrine overhead
    - Default preferences: in_app=true (opt-out), email=false (opt-in)
    - InApp Mercure topics per-user (/users/{id}/notifications) not org-wide

key-files:
  created:
    - backend/src/Notification/Domain/Entity/NotificationPreference.php
    - backend/src/Notification/Domain/ValueObject/NotificationChannel.php
    - backend/src/Notification/Domain/ValueObject/NotificationPreferenceId.php
    - backend/src/Notification/Domain/Repository/NotificationPreferenceRepositoryInterface.php
    - backend/src/Notification/Infrastructure/Repository/DoctrineNotificationPreferenceRepository.php
    - backend/src/Notification/Infrastructure/Persistence/Doctrine/Mapping/NotificationPreference.orm.xml
    - backend/src/Notification/Infrastructure/Mercure/NotificationMercurePublisher.php
    - backend/src/Notification/Application/Port/NotificationMailerInterface.php
    - backend/src/Notification/Infrastructure/Email/SymfonyNotificationMailer.php
    - backend/src/Notification/Application/Service/NotificationDispatcher.php
    - backend/src/Notification/Application/EventHandler/OnProcessStarted.php
    - backend/src/Notification/Application/EventHandler/OnProcessCompleted.php
    - backend/src/Notification/Application/EventHandler/OnProcessCancelled.php
    - backend/src/Notification/Application/EventHandler/OnInvitationCreated.php
    - backend/src/Notification/Application/DTO/NotificationPreferenceDTO.php
    - backend/src/Notification/Application/Query/GetPreferences/GetPreferencesQuery.php
    - backend/src/Notification/Application/Query/GetPreferences/GetPreferencesHandler.php
    - backend/src/Notification/Application/Command/SavePreferences/SavePreferencesCommand.php
    - backend/src/Notification/Application/Command/SavePreferences/SavePreferencesHandler.php
    - backend/migrations/Version20260301200000.php
    - backend/templates/email/notification/task_assigned.html.twig
    - backend/templates/email/notification/task_completed.html.twig
    - backend/templates/email/notification/process_started.html.twig
    - backend/templates/email/notification/process_completed.html.twig
    - backend/templates/email/notification/process_cancelled.html.twig
    - backend/templates/email/notification/comment_added.html.twig
    - backend/templates/email/notification/invitation_received.html.twig
  modified:
    - backend/src/Notification/Domain/Entity/Notification.php
    - backend/src/Notification/Domain/ValueObject/NotificationType.php
    - backend/src/Notification/Domain/Repository/NotificationRepositoryInterface.php
    - backend/src/Notification/Infrastructure/Repository/DoctrineNotificationRepository.php
    - backend/src/Notification/Infrastructure/Persistence/Doctrine/Mapping/Notification.orm.xml
    - backend/src/Notification/Application/DTO/NotificationDTO.php
    - backend/src/Notification/Application/Query/ListNotifications/ListNotificationsQuery.php
    - backend/src/Notification/Application/Query/ListNotifications/ListNotificationsHandler.php
    - backend/src/Notification/Application/EventHandler/OnTaskAssigned.php
    - backend/src/Notification/Application/EventHandler/OnTaskStatusChanged.php
    - backend/src/Notification/Application/EventHandler/OnCommentAdded.php
    - backend/src/Notification/Presentation/Controller/NotificationController.php
    - backend/config/packages/messenger.yaml
    - backend/config/services.yaml

key-decisions:
  - "NotificationDispatcher is central: all event handlers inject it instead of direct repository access"
  - "in_app preference defaults to enabled (opt-out), email defaults to disabled (opt-in)"
  - "OnProcessCompleted uses DBAL instead of Doctrine — keeps sync handler lightweight (no Doctrine unit of work overhead)"
  - "InvitationCreatedEvent now routed async — invitation email sent by InviteUserHandler, in-app via OnInvitationCreated"
  - "OnInvitationCreated skips email entirely — already sent by InvitationMailerInterface in InviteUserHandler"
  - "TaskCompleted is separate NotificationType from TaskStatusChanged — added for Plan NOTF-07 requirement"

requirements-completed: [NOTF-01, NOTF-02, NOTF-03, NOTF-04, NOTF-07]

# Metrics
duration: 8min
completed: 2026-03-01
---

# Phase 09 Plan 01: Backend Notification System Summary

**Notification domain extended with channel/preferences/readAt, NotificationDispatcher centralizes Mercure SSE + email delivery with per-user preference control across 7 event trigger types**

## Performance

- **Duration:** 8 min
- **Started:** 2026-03-01T19:14:46Z
- **Completed:** 2026-03-01T19:22:26Z
- **Tasks:** 3
- **Files modified:** 41

## Accomplishments

- Extended Notification entity with channel, relatedEntityType, readAt; updated ORM mapping and migration adds 3 columns + notification_preferences table
- Created NotificationDispatcher service that centralizes: preference check (in_app opt-out, email opt-in) → DB save → Mercure SSE publish → email send
- Implemented 7 event handlers (3 refactored, 4 new) and preferences CRUD API (GET returns default map, PUT saves bulk)

## Task Commits

1. **Task 1: Domain model — entities, VOs, ORM mappings, repositories, migration** - `ecc0617` (feat)
2. **Task 2: CQRS handlers, DTOs, and controller endpoints** - `7c61206` (feat)
3. **Task 3: NotificationDispatcher, Mercure publisher, email mailer, event handlers** - `3dd99b3` (feat)

## Files Created/Modified

- `backend/src/Notification/Domain/Entity/Notification.php` - Added channel, relatedEntityType, readAt fields; markAsRead sets readAt
- `backend/src/Notification/Domain/Entity/NotificationPreference.php` - New entity: userId, eventType, channel, enabled
- `backend/src/Notification/Domain/ValueObject/NotificationType.php` - Added 5 new types (ProcessStarted, ProcessCompleted, ProcessCancelled, InvitationReceived, TaskCompleted)
- `backend/src/Notification/Domain/ValueObject/NotificationChannel.php` - New enum: in_app, email
- `backend/src/Notification/Application/Service/NotificationDispatcher.php` - Central dispatch service with preference-aware routing
- `backend/src/Notification/Infrastructure/Mercure/NotificationMercurePublisher.php` - Mercure SSE publisher to /users/{id}/notifications
- `backend/src/Notification/Infrastructure/Email/SymfonyNotificationMailer.php` - Twig-rendered email via Symfony Mailer
- `backend/src/Notification/Application/EventHandler/OnProcessCompleted.php` - Sync handler using DBAL (no Doctrine overhead)
- `backend/src/Notification/Application/EventHandler/OnInvitationCreated.php` - Checks if user exists before in-app notification
- `backend/src/Notification/Presentation/Controller/NotificationController.php` - Added GET/PUT /preferences endpoints, ?type= filter
- `backend/migrations/Version20260301200000.php` - Migration: channel/related_entity_type/read_at + notification_preferences table
- `backend/config/packages/messenger.yaml` - InvitationCreatedEvent routed async
- `backend/templates/email/notification/*.html.twig` - 7 email templates (simple inline-style HTML)

## Decisions Made

- **NotificationDispatcher pattern:** All event handlers use the dispatcher instead of direct repository access. Encapsulates the full delivery pipeline with a single dispatch() call.
- **Email defaults to opt-in (disabled):** Prevents unwanted emails; users explicitly enable email channel per event type.
- **OnProcessCompleted uses DBAL:** ProcessCompletedEvent is NOT routed async (OnSubProcessCompleted must run synchronously). Using DBAL fetchAssociative keeps the handler lightweight.
- **OnInvitationCreated no email:** InviteUserHandler already sends invitation email via InvitationMailerInterface. Sending a second email would be spam.
- **InvitationCreatedEvent routed async:** Added to messenger.yaml so OnInvitationCreated runs asynchronously (non-blocking).

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Full backend notification API ready for frontend integration (Plan 09-02)
- GET /api/v1/notifications returns enriched DTOs with channel, readAt, relatedEntityType
- GET /api/v1/notifications/preferences returns 8-type map with defaults
- PUT /api/v1/notifications/preferences saves bulk preferences
- Mercure SSE publishes to /users/{id}/notifications on new in_app notification
- Email sends asynchronously (queued) when user opted-in

## Self-Check: PASSED

All files created, all commits verified.

---
*Phase: 09-notification-system*
*Completed: 2026-03-01*
