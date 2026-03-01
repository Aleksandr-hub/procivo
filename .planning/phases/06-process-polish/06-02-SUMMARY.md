---
phase: 06-process-polish
plan: 02
subsystem: workflow
tags: [symfony, cqrs, doctrine-dbal, vue3, pinia, primevue, i18n, process-definition, version-history, migration]

# Dependency graph
requires:
  - phase: 06-01
    provides: "PublishProcessDefinition works from Published state (re-publish flow)"

provides:
  - "GET /api/v1/organizations/{org}/process-definitions/{id}/versions — list versions sorted newest first"
  - "POST /api/v1/organizations/{org}/process-definitions/{id}/versions/{versionId}/migrate — migrate running instances to target version"
  - "ProcessDefinitionVersionDTO with version_number, published_at, published_by"
  - "ListVersionsQuery + ListVersionsHandler (query.bus)"
  - "MigrateProcessInstancesCommand + MigrateProcessInstancesHandler (command.bus)"
  - "Designer deploy button (replaces Publish/RevertToDraft pair)"
  - "Version badge (vN) in Designer header"

affects: [07-process-instances, 08-audit, process-definition-store]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Schema compatibility validation before instance migration: check active token node_ids against target version's snapshot"
    - "nodesSnapshot stored as { nodes: [], transitions: [] } — access via $snapshot['nodes'] for node IDs"
    - "Read model (workflow_process_instances_view) updated on migration; event store immutable"
    - "currentVersion computed from versions[0].version_number (sorted newest first)"

key-files:
  created:
    - backend/src/Workflow/Application/DTO/ProcessDefinitionVersionDTO.php
    - backend/src/Workflow/Application/Query/ListVersions/ListVersionsQuery.php
    - backend/src/Workflow/Application/Query/ListVersions/ListVersionsHandler.php
    - backend/src/Workflow/Application/Command/MigrateProcessInstances/MigrateProcessInstancesCommand.php
    - backend/src/Workflow/Application/Command/MigrateProcessInstances/MigrateProcessInstancesHandler.php
  modified:
    - backend/src/Workflow/Presentation/Controller/ProcessDefinitionController.php
    - frontend/src/modules/workflow/types/process-definition.types.ts
    - frontend/src/modules/workflow/api/process-definition.api.ts
    - frontend/src/modules/workflow/stores/process-definition.store.ts
    - frontend/src/modules/workflow/pages/ProcessDesignerPage.vue
    - frontend/src/i18n/locales/uk.json
    - frontend/src/i18n/locales/en.json

key-decisions:
  - "nodesSnapshot()['nodes'] accessed explicitly for node ID extraction — snapshot is { nodes, transitions }, not flat array"
  - "MigrateProcessInstancesHandler does not inject ProcessDefinitionRepositoryInterface — not needed since we only validate via versionRepository"
  - "Deploy button always visible (draft and published) — no more conditional Publish/RevertToDraft pair"
  - "fetchVersions called in onMounted and after publishDefinition — version badge stays fresh"

patterns-established:
  - "ProcessDefinitionVersionDTO: follows same readonly + JsonSerializable pattern as other DTOs"
  - "ListVersionsHandler: sorting done in PHP (usort) not SQL, consistent with other list handlers"
  - "MigrateProcessInstancesHandler: raw DBAL Connection used for read model updates, not Doctrine ORM"

requirements-completed: [PLSH-07]

# Metrics
duration: 14min
completed: 2026-03-01
---

# Phase 06 Plan 02: Version History API and Deploy Flow Summary

**Version history API (GET /versions), instance migration with schema validation, and Designer deploy button replacing the Publish/RevertToDraft pair with version badge**

## Performance

- **Duration:** ~14 min
- **Started:** 2026-03-01T13:50:03Z
- **Completed:** 2026-03-01T14:03:30Z
- **Tasks:** 2
- **Files modified:** 11

## Accomplishments
- Added GET `/{definitionId}/versions` endpoint returning version history sorted newest first
- Added POST `/{definitionId}/versions/{versionId}/migrate` with schema compatibility validation (active token nodes must exist in target version)
- Replaced Designer's conditional Publish/RevertToDraft buttons with single Deploy button, version badge, and deploy success toast

## Task Commits

Each task was committed atomically:

1. **Task 1: Add version history API and instance migration endpoint** - `80d6b33` (feat)
2. **Task 2: Update Designer UI with deploy flow and version indicator** - `58eef17` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `backend/src/Workflow/Application/DTO/ProcessDefinitionVersionDTO.php` - DTO with version_number, published_at, published_by; JsonSerializable
- `backend/src/Workflow/Application/Query/ListVersions/ListVersionsQuery.php` - Query with processDefinitionId
- `backend/src/Workflow/Application/Query/ListVersions/ListVersionsHandler.php` - Handler sorting versions newest first, #[AsMessageHandler(bus: 'query.bus')]
- `backend/src/Workflow/Application/Command/MigrateProcessInstances/MigrateProcessInstancesCommand.php` - Command with processDefinitionId, targetVersionId, migratedBy
- `backend/src/Workflow/Application/Command/MigrateProcessInstances/MigrateProcessInstancesHandler.php` - Handler validating active token nodes, updating read model via DBAL
- `backend/src/Workflow/Presentation/Controller/ProcessDefinitionController.php` - Added versions() and migrateInstances() actions
- `frontend/src/modules/workflow/types/process-definition.types.ts` - Added ProcessDefinitionVersionDTO interface
- `frontend/src/modules/workflow/api/process-definition.api.ts` - Added versions() method
- `frontend/src/modules/workflow/stores/process-definition.store.ts` - Added versions ref, currentVersion computed, fetchVersions action; publishDefinition now refetches versions
- `frontend/src/modules/workflow/pages/ProcessDesignerPage.vue` - Deploy button + version badge, handleDeploy with toast, fetchVersions on mount
- `frontend/src/i18n/locales/uk.json` + `en.json` - Added deploy, deploySuccess, deployedVersion, deployFailed, version keys

## Decisions Made
- **nodesSnapshot structure**: The snapshot stored in `ProcessDefinitionVersion.nodesSnapshot` is `{ nodes: [], transitions: [] }`, not a flat array. Fixed extraction to use `$snapshot['nodes']` for node ID validation in MigrateProcessInstancesHandler.
- **No ProcessDefinitionRepositoryInterface injection**: Handler only needs versionRepository + DBAL Connection — kept minimal.
- **Deploy button always visible**: Since Plan 01 makes publish() work from Published state, no need for conditional display — simpler UX.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed incorrect nodesSnapshot access in MigrateProcessInstancesHandler**
- **Found during:** Task 1 (MigrateProcessInstancesHandler implementation)
- **Issue:** Plan showed `array_map(fn => $node['id'], $targetVersion->nodesSnapshot())` treating snapshot as flat array, but nodesSnapshot() returns `{ nodes: [...], transitions: [...] }` (confirmed from PublishProcessDefinitionHandler and ProcessGraph::fromSnapshot())
- **Fix:** Changed to `$snapshot['nodes'] ?? []` before mapping node IDs
- **Files modified:** `backend/src/Workflow/Application/Command/MigrateProcessInstances/MigrateProcessInstancesHandler.php`
- **Verification:** PHPStan level 6 passes, no type errors
- **Committed in:** `80d6b33` (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (Rule 1 - Bug)
**Impact on plan:** Fix necessary for correctness — without it, migration would always fail or match wrong IDs. No scope creep.

## Issues Encountered
- `php bin/console lint:container` fails with Redis class error — pre-existing issue unrelated to this plan (Redis PHP extension not loaded locally). PHPStan used instead for DI validation.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Version history API ready for use by frontend history panel or instance detail page
- Migration API available for admin use cases
- Designer deploy flow is cleaner — no more revert-to-draft dance
- PLSH-07 complete

## Self-Check: PASSED

All created files exist on disk. Both task commits verified in git history.
