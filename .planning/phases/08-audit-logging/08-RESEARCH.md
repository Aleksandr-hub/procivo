# Phase 8: Audit Logging - Research

**Researched:** 2026-03-01
**Domain:** Audit trail system — async domain event consumption, JSONB change storage, filtered paginated REST API, Vue 3 timeline UI
**Confidence:** HIGH

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| AUDT-01 | Domain events carry actorId (passed through command context for async workers) | actorId propagation via event constructor field; DomainEvent interface, AggregateRoot, and DispatchDomainEventsMiddleware patterns identified; approach: add `actorId` field to every auditable event |
| AUDT-02 | AuditLog entity persists event_type, actor, entity, changes JSONB, timestamp — async via event.bus | Notification module provides exact template (entity, Doctrine XML mapping, async event handler pattern with `#[AsMessageHandler(bus: 'event.bus')]`); Doctrine ORM 3.x JSONB type via `json` column type |
| AUDT-03 | User can query GET /api/v1/audit-log with filters (entity type, actor, date range) and paginated results | ListProcessInstancesHandler DBAL QueryBuilder pattern with clone-for-count and ILIKE is the proven in-project pagination pattern; AuditLog is read-side only — use DBAL, not ORM |
| AUDT-04 | Activity timeline displayed on task detail, process instance detail, and organization detail pages | ProcessHistoryTimeline.vue (PrimeVue Timeline component) already exists and is the correct template for activity timelines; audit log API replaces the current event store history endpoint for cross-cutting timeline |
</phase_requirements>

---

## Summary

Phase 8 adds a cross-cutting audit trail: every significant business event is recorded asynchronously as an `AuditLog` entry with full actor attribution, then exposed through a filtered REST API and rendered in Vue timelines on detail pages.

The key challenge is **actorId propagation through async Messenger workers**. The Symfony Security token is not available in async consumers — the current `SecurityCurrentUserProvider` would throw at runtime. The correct pattern, already proven in the codebase by `ProcessStartedEvent.startedBy`, `ProcessCancelledEvent.cancelledBy`, and `TaskCreatedEvent.creatorId`, is to pass the actor ID explicitly in the domain event constructor. Controllers already obtain the user ID from `CurrentUserProviderInterface` before dispatching commands; commands pass it to aggregates; aggregates embed it in domain events. Audit handlers then read `event->actorId` directly — no Security call needed.

The `Notification` module is the closest existing analogue: it has a Domain entity, Doctrine XML mapping, `DoctrineRepository`, event handlers registered on `event.bus`, and a presentation controller. The `Audit` module should mirror this structure. The main difference is that AuditLog is **append-only and read-heavy** — its query handler should use `Doctrine\DBAL\Connection` (not ORM) following the `ListProcessInstancesHandler` pattern, and the Doctrine ORM entity is only needed for writes via `EntityManagerInterface`.

**Primary recommendation:** Create an `Audit` module following the Notification module's Clean Architecture structure; add `actorId` to all auditable domain events as an explicit constructor field; route all auditable events to `async` transport in `messenger.yaml`; use the existing PrimeVue `Timeline` component (already proven in `ProcessHistoryTimeline.vue`) for activity timelines.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Symfony Messenger | 8.0.* | Async event handling | Already in project; event.bus with `async` transport via RabbitMQ |
| Doctrine ORM | 3.6 | AuditLog entity persistence (write side) | Project standard; XML mappings |
| Doctrine DBAL | 3.x (bundled) | Filtered paginated query (read side) | Already used in ListProcessInstancesHandler for performance |
| PrimeVue Timeline | 4.5.4 | Activity timeline component | Already used in ProcessHistoryTimeline.vue |
| PostgreSQL JSONB | 18 | `changes` column for before/after diff | Doctrine `json` type maps to JSONB in PostgreSQL |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Symfony UID | 8.0.* | UUID generation for AuditLog IDs | Already used (TaskId::generate(), etc.) |
| Doctrine Migrations | 4.0 | audit_log table schema | Already used — new migration file needed |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom Audit module | Doctrine Extensions Loggable | Loggable is ORM-annotation-based, auto-tracks all changes — too coarse, no actorId from JWT |
| DBAL for queries | ORM Repository | ORM adds complexity for filtered/paginated reads; DBAL is faster and already proven in project |
| Separate `actorId` Messenger Stamp | actorId in event constructor | Stamp approach is more complex (custom Stamp class + middleware); event field is simpler and matches existing project events (`ProcessStartedEvent.startedBy`) |

---

## Architecture Patterns

### Recommended Project Structure

```
src/Audit/
├── Domain/
│   ├── Entity/
│   │   └── AuditLog.php              # append-only entity: id, eventType, actorId, entityType, entityId, changes (JSONB), occurredAt
│   ├── Repository/
│   │   └── AuditLogRepositoryInterface.php
│   └── ValueObject/
│       └── AuditLogId.php
├── Application/
│   ├── DTO/
│   │   └── AuditLogDTO.php
│   ├── EventHandler/                 # one handler per auditable event group (or one handler per event)
│   │   ├── OnTaskAuditableEvent.php  # handles TaskCreatedEvent, TaskStatusChangedEvent, TaskAssignedEvent, TaskClaimedEvent, TaskDeletedEvent
│   │   ├── OnProcessAuditableEvent.php # handles ProcessStartedEvent, ProcessCompletedEvent, ProcessCancelledEvent
│   │   └── OnAuthAuditableEvent.php  # handles UserRegisteredEvent, UserActivatedEvent, PasswordChangedEvent
│   └── Query/
│       └── ListAuditLog/
│           ├── ListAuditLogQuery.php
│           └── ListAuditLogHandler.php
├── Infrastructure/
│   ├── Persistence/
│   │   └── Doctrine/
│   │       ├── Mapping/
│   │       │   └── AuditLog.orm.xml
│   │       └── Repository/
│   │           └── DoctrineAuditLogRepository.php
└── Presentation/
    └── Controller/
        └── AuditLogController.php    # GET /api/v1/organizations/{orgId}/audit-log
```

### Pattern 1: actorId in Domain Events

**What:** Every domain event that needs audit attribution carries an explicit `actorId: string` field.
**When to use:** All events fired by user-initiated commands. System-initiated events (timer fired, sub-process activated) use a sentinel actorId like `'system'`.

**Existing pattern in codebase (HIGH confidence):**
```php
// src/Workflow/Domain/Event/ProcessStartedEvent.php — already has actor field
final readonly class ProcessStartedEvent implements DomainEvent
{
    public function __construct(
        public string $processInstanceId,
        public string $organizationId,
        public string $startedBy,  // <-- actorId already present
        // ...
    ) {}
}

// src/Workflow/Domain/Event/ProcessCancelledEvent.php — already has actor field
final readonly class ProcessCancelledEvent implements DomainEvent
{
    public function __construct(
        public string $processInstanceId,
        public string $cancelledBy,  // <-- actorId already present
        public ?string $reason,
        // ...
    ) {}
}
```

**Events that NEED actorId added:**
- `TaskStatusChangedEvent` — add `actorId: string`
- `TaskAssignedEvent` — add `actorId: string` (assigner)
- `TaskClaimedEvent` — add `actorId: string` (claimer's userId)
- `TaskDeletedEvent` — add `actorId: string`
- `CommentAddedEvent` — already has `authorId` (check if it can serve as actorId)
- `UserRegisteredEvent`, `UserActivatedEvent`, `PasswordChangedEvent` — add `actorId: string`

**Events that ALREADY have actorId (no change needed):**
- `ProcessStartedEvent.startedBy`
- `ProcessCancelledEvent.cancelledBy`
- `TaskCreatedEvent.creatorId`

### Pattern 2: Async Event Handler for AuditLog

**What:** `#[AsMessageHandler(bus: 'event.bus')]` handler that receives a domain event, maps it to an `AuditLog` entry, persists via repository.
**When to use:** Every auditable domain event.

**Template from existing codebase (HIGH confidence):**
```php
// mirrors src/Notification/Application/EventHandler/OnTaskAssigned.php
#[AsMessageHandler(bus: 'event.bus')]
final readonly class OnTaskAuditableEvent
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    public function __invoke(TaskStatusChangedEvent $event): void
    {
        $this->auditLogRepository->save(AuditLog::record(
            id: AuditLogId::generate(),
            eventType: $event->eventName(),       // 'task_manager.task.status_changed'
            actorId: $event->actorId,             // passed explicitly in event
            entityType: 'task',
            entityId: $event->taskId,
            changes: ['from' => $event->oldStatus, 'to' => $event->newStatus],
            occurredAt: $event->occurredAt(),
        ));
    }
}
```

### Pattern 3: Doctrine XML Mapping with JSONB

**What:** AuditLog.orm.xml maps `changes` to `json` type (PostgreSQL stores as JSONB).
**When to use:** Any column needing dynamic key-value storage.

**Template from existing Notification.orm.xml (HIGH confidence):**
```xml
<!-- src/Audit/Infrastructure/Persistence/Doctrine/Mapping/AuditLog.orm.xml -->
<entity name="App\Audit\Domain\Entity\AuditLog" table="audit_log">
    <id name="id" type="string" length="36">
        <generator strategy="NONE"/>
    </id>
    <field name="eventType" column="event_type" type="string" length="100"/>
    <field name="actorId" column="actor_id" type="string" length="36" nullable="true"/>
    <field name="entityType" column="entity_type" type="string" length="50"/>
    <field name="entityId" column="entity_id" type="string" length="36"/>
    <field name="organizationId" column="organization_id" type="string" length="36" nullable="true"/>
    <field name="changes" column="changes" type="json" nullable="true"/>
    <field name="occurredAt" column="occurred_at" type="datetime_immutable"/>
    <indexes>
        <index name="idx_audit_log_entity" columns="entity_type,entity_id,occurred_at"/>
        <index name="idx_audit_log_actor" columns="actor_id,occurred_at"/>
        <index name="idx_audit_log_org" columns="organization_id,occurred_at"/>
    </indexes>
</entity>
```

### Pattern 4: Paginated Filtered Query with DBAL

**What:** `ListAuditLogHandler` uses `Doctrine\DBAL\Connection` QueryBuilder with clone-for-count, filter conditions, LIMIT/OFFSET.
**When to use:** Read-side queries with filters and pagination.

**Template from existing ListProcessInstancesHandler (HIGH confidence):**
```php
#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListAuditLogHandler
{
    public function __construct(private Connection $connection) {}

    public function __invoke(ListAuditLogQuery $query): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->from('audit_log')
            ->where('organization_id = :orgId')
            ->setParameter('orgId', $query->organizationId);

        if (null !== $query->entityType) {
            $qb->andWhere('entity_type = :entityType')
               ->setParameter('entityType', $query->entityType);
        }

        if (null !== $query->actorId) {
            $qb->andWhere('actor_id = :actorId')
               ->setParameter('actorId', $query->actorId);
        }

        if (null !== $query->dateFrom) {
            $qb->andWhere('occurred_at >= :dateFrom')
               ->setParameter('dateFrom', $query->dateFrom->format('Y-m-d H:i:s'));
        }

        if (null !== $query->dateTo) {
            $qb->andWhere('occurred_at <= :dateTo')
               ->setParameter('dateTo', $query->dateTo->format('Y-m-d H:i:s'));
        }

        // Clone BEFORE adding LIMIT/OFFSET — key insight from Phase 6 research
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(*)')->executeQuery()->fetchOne();

        $offset = ($query->page - 1) * $query->limit;
        $qb->select('*')
           ->orderBy('occurred_at', 'DESC')
           ->setMaxResults($query->limit)
           ->setFirstResult($offset);

        $rows = $qb->executeQuery()->fetchAllAssociative();

        return [
            'items' => array_map(AuditLogDTO::fromRow(...), $rows),
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }
}
```

### Pattern 5: Frontend Activity Timeline

**What:** Reuse PrimeVue `<Timeline>` component (already proven in `ProcessHistoryTimeline.vue`) for audit log entries.
**When to use:** Task detail, process instance detail, organization detail pages.

**Key insight:** `ProcessHistoryTimeline.vue` reads from the workflow event store. The new `AuditLogTimeline.vue` reads from `GET /api/v1/organizations/{orgId}/audit-log?entity_type=task&entity_id={id}`. The component structure is identical — same PrimeVue Timeline, same scoped slots, same icon/color mapping.

### Pattern 6: Messenger Transport Routing for Audit Events

**What:** Every new auditable domain event class must be explicitly routed to `async` in `messenger.yaml`.
**When to use:** Any event that must not block the HTTP request and must survive RabbitMQ restarts via the `failed` transport.

**Template from existing messenger.yaml (HIGH confidence):**
```yaml
routing:
    # Audit events → async (RabbitMQ)
    App\TaskManager\Domain\Event\TaskStatusChangedEvent: async
    App\TaskManager\Domain\Event\TaskAssignedEvent: async
    App\TaskManager\Domain\Event\TaskClaimedEvent: async
    App\TaskManager\Domain\Event\TaskUnclaimedEvent: async
    App\TaskManager\Domain\Event\TaskDeletedEvent: async
    App\TaskManager\Domain\Event\CommentAddedEvent: async
    App\Workflow\Domain\Event\ProcessStartedEvent: async
    App\Workflow\Domain\Event\ProcessCompletedEvent: async
    App\Workflow\Domain\Event\ProcessCancelledEvent: async
    App\Identity\Domain\Event\UserRegisteredEvent: async
```

Note: `TaskStatusChangedEvent` and `TaskAssignedEvent` are already routed to `async` (for Notification handlers). Adding Audit handlers to the same events requires NO routing change — Messenger fan-outs to all handlers registered for that event.

### Anti-Patterns to Avoid

- **Calling Security::getUser() in async handler:** `SecurityCurrentUserProvider` calls `$this->security->getUser()` — the Security token is null in an async Messenger consumer. Always read `actorId` from the event itself.
- **Saving actorId via Middleware that intercepts all events:** A middleware approach is tempting but breaks the single-responsibility principle. Event fields are explicit, visible, testable.
- **Using ORM for paginated reads:** For filtered/paginated audit log queries, use DBAL QueryBuilder. ORM hydration overhead is unnecessary for read-side.
- **Routing events to `sync://` for audit:** Audit handlers must be async — a slow DB write in a sync handler would degrade the HTTP response time.
- **Single mega-handler for all events:** One handler per event type or one per module group (e.g., `OnTaskAuditableEvent` handles all TaskManager events). PHP Messenger dispatch is type-matched — one `__invoke` per type.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| JSONB storage for changes | Custom serializer | Doctrine `json` type | Maps cleanly to PostgreSQL JSONB; handles encode/decode |
| Async event fan-out | Second event bus | Symfony Messenger multi-handler | Messenger dispatches one message to ALL registered handlers automatically |
| UUID for AuditLog ID | Custom generator | `Symfony\Component\Uid\Uuid::v4()` (already used via Uuid value object) | Consistent with project |
| Timeline UI from scratch | Custom CSS timeline | PrimeVue `<Timeline>` | Already in project, proven in ProcessHistoryTimeline.vue |
| Pagination | Custom OFFSET calc | DBAL QueryBuilder clone pattern | Already proven in ListProcessInstancesHandler |

**Key insight:** The biggest complexity risk is forgetting to add `actorId` to an event before routing it to async. The audit handler will throw if it tries to read a field that does not exist. Add the field first, update callers, then write the handler.

---

## Common Pitfalls

### Pitfall 1: actorId Missing from Async Event Consumer
**What goes wrong:** Handler does `$event->actorId` but the event class has no such field. PHP fatal error in async worker, goes to `failed` queue silently.
**Why it happens:** Event was written before audit requirement; nobody added the field.
**How to avoid:** Add `actorId: string` to the event constructor FIRST. Update every place that constructs the event. Then write the handler.
**Warning signs:** Events accumulating in `failed` queue. Check `symfony messenger:failed:show`.

### Pitfall 2: Security Token Null in Async Worker
**What goes wrong:** `SecurityCurrentUserProvider::getUserId()` throws `RuntimeException: No authenticated user` in async consumer.
**Why it happens:** Async Messenger workers run outside HTTP context; Symfony Security has no token.
**How to avoid:** NEVER inject `CurrentUserProviderInterface` or `Security` into an audit event handler. Read actor from event field only.
**Warning signs:** `RuntimeException: No authenticated user` in worker logs.

### Pitfall 3: COUNT Query Incorrect After LIMIT Applied
**What goes wrong:** `total` returns the count of the current page (e.g., 20) instead of the true total (e.g., 847).
**Why it happens:** Cloning `$qb` AFTER `setMaxResults()` carries the LIMIT into the COUNT query.
**How to avoid:** `$countQb = clone $qb;` BEFORE `setMaxResults()` and `setFirstResult()`. Already documented in STATE.md Phase 6 decisions.
**Warning signs:** Pagination stops at page 2 even when more records exist; `total` equals `limit`.

### Pitfall 4: Missing Route in messenger.yaml
**What goes wrong:** New event class is dispatched to `sync://` by default. Audit handler runs synchronously in the HTTP request, blocking response.
**Why it happens:** Symfony Messenger routes unknown messages to the default bus middleware (sync) if no explicit routing is defined.
**How to avoid:** For every new auditable event class, add an entry to `messenger.yaml` routing section immediately.
**Warning signs:** Response time increases; no messages in RabbitMQ queue.

### Pitfall 5: organizationId Not Available on AuditLog for Some Events
**What goes wrong:** `GET /audit-log?entity_type=task` cannot be scoped to organization because `TaskStatusChangedEvent` does not carry `organizationId`.
**Why it happens:** Task events were designed before audit requirement; organization scoping was not needed for notification handlers.
**How to avoid:** During event enrichment (AUDT-01), also add `organizationId` to TaskManager events that lack it, OR resolve organizationId in the audit handler by loading the Task entity (one extra DB read per audit event — acceptable for async).
**Warning signs:** Audit API returns entries from all organizations to any authenticated user.

### Pitfall 6: PrimeVue Timeline Requires Array, Not Object
**What goes wrong:** `<Timeline :value="entries">` renders nothing if `entries` is an object `{}` instead of an array `[]`.
**Why it happens:** API returns `{ items: [...], total: ... }` — developer passes the response object directly.
**How to avoid:** Use `auditLogs.value = response.items` not `response`.
**Warning signs:** Timeline renders empty even though API returns data.

---

## Code Examples

### AuditLog Domain Entity

```php
// src/Audit/Domain/Entity/AuditLog.php
final class AuditLog
{
    private string $id;
    private string $eventType;
    private ?string $actorId;
    private string $entityType;
    private string $entityId;
    private ?string $organizationId;
    private ?array $changes;
    private \DateTimeImmutable $occurredAt;

    private function __construct() {}

    /**
     * @param array<string, mixed>|null $changes
     */
    public static function record(
        AuditLogId $id,
        string $eventType,
        ?string $actorId,
        string $entityType,
        string $entityId,
        ?string $organizationId,
        ?array $changes,
        \DateTimeImmutable $occurredAt,
    ): self {
        $log = new self();
        $log->id = $id->value();
        $log->eventType = $eventType;
        $log->actorId = $actorId;
        $log->entityType = $entityType;
        $log->entityId = $entityId;
        $log->organizationId = $organizationId;
        $log->changes = $changes;
        $log->occurredAt = $occurredAt;

        return $log;
    }
    // getters...
}
```

### AuditLog REST API Endpoint

```php
// src/Audit/Presentation/Controller/AuditLogController.php
#[Route('/api/v1/organizations/{organizationId}/audit-log', name: 'api_v1_audit_log_')]
final readonly class AuditLogController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'AUDIT_VIEW');

        $result = $this->queryBus->ask(new ListAuditLogQuery(
            organizationId: $organizationId,
            entityType: $request->query->get('entity_type'),
            entityId: $request->query->get('entity_id'),
            actorId: $request->query->get('actor_id'),
            dateFrom: $request->query->get('date_from')
                ? new \DateTimeImmutable($request->query->get('date_from'))
                : null,
            dateTo: $request->query->get('date_to')
                ? new \DateTimeImmutable($request->query->get('date_to'))
                : null,
            page: max(1, (int) $request->query->get('page', '1')),
            limit: min(100, max(1, (int) $request->query->get('limit', '50'))),
        ));

        return new JsonResponse($result);
    }
}
```

### Frontend AuditLogTimeline Component (Vue 3)

```typescript
// src/modules/audit/api/audit-log.api.ts
import httpClient from '@/shared/api/http-client'
import type { AuditLogDTO, AuditLogListResponse } from '@/modules/audit/types/audit-log.types'

export const auditLogApi = {
  list(
    orgId: string,
    params: {
      entity_type?: string
      entity_id?: string
      actor_id?: string
      date_from?: string
      date_to?: string
      page?: number
      limit?: number
    } = {},
  ): Promise<AuditLogListResponse> {
    return httpClient
      .get(`/organizations/${orgId}/audit-log`, { params })
      .then((r) => r.data)
  },
}
```

```vue
<!-- src/modules/audit/components/AuditLogTimeline.vue — mirrors ProcessHistoryTimeline.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { auditLogApi } from '@/modules/audit/api/audit-log.api'
import { formatDateTime } from '@/shared/utils/date-format'
import type { AuditLogDTO } from '@/modules/audit/types/audit-log.types'

const props = defineProps<{
  orgId: string
  entityType: string
  entityId: string
}>()

const entries = ref<AuditLogDTO[]>([])
const loading = ref(false)

const eventConfig: Record<string, { icon: string; color: string; label: string }> = {
  'task_manager.task.created': { icon: 'pi pi-plus', color: 'purple', label: 'Task created' },
  'task_manager.task.status_changed': { icon: 'pi pi-refresh', color: 'blue', label: 'Status changed' },
  'task_manager.task.assigned': { icon: 'pi pi-user', color: 'blue', label: 'Assigned' },
  'workflow.process.started': { icon: 'pi pi-play', color: 'purple', label: 'Process started' },
  'workflow.process.completed': { icon: 'pi pi-flag', color: 'green', label: 'Process completed' },
  'workflow.process.cancelled': { icon: 'pi pi-times-circle', color: 'red', label: 'Process cancelled' },
  // ...
}

onMounted(async () => {
  loading.value = true
  try {
    const result = await auditLogApi.list(props.orgId, {
      entity_type: props.entityType,
      entity_id: props.entityId,
      limit: 50,
    })
    entries.value = result.items
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <Timeline :value="entries" align="left">
    <template #marker="{ item }">
      <span class="timeline-marker" :class="eventConfig[item.event_type]?.color ?? 'gray'">
        <i :class="eventConfig[item.event_type]?.icon ?? 'pi pi-circle'" />
      </span>
    </template>
    <template #content="{ item }">
      <div class="timeline-event">
        <div class="event-title">{{ eventConfig[item.event_type]?.label ?? item.event_type }}</div>
        <div v-if="item.changes" class="event-details">
          <div v-for="(value, key) in item.changes" :key="key" class="detail-row">
            <span class="detail-key">{{ key }}:</span>
            <span class="detail-value">{{ value }}</span>
          </div>
        </div>
        <small class="event-time">{{ formatDateTime(item.occurred_at) }}</small>
      </div>
    </template>
  </Timeline>
</template>
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Audit via DB triggers | Domain event handlers on event.bus | N/A (design decision) | Clean separation; actor attribution is explicit |
| Symfony `switch_user` for actor tracking | actorId in event payload | N/A | JWT firewall is stateless; Security token unavailable async |
| ORM for read-heavy queries | DBAL QueryBuilder | Phase 6 (pagination) | Proven in project for filtered paginated results |

**Known gap in project:** Currently `TaskStatusChangedEvent` does NOT carry `actorId`. The Notification handler works without it because notifications go to the assignee (not the actor). Adding `actorId` is a non-breaking change if done carefully — all existing callers must be updated.

---

## Key Events to Audit (Scope)

Based on the success criteria and existing domain events:

### Task Lifecycle (AUDT-02)
| Event | Entity Type | actorId Field | Already Has? |
|-------|-------------|---------------|-------------|
| `TaskCreatedEvent` | `task` | `creatorId` | YES |
| `TaskStatusChangedEvent` | `task` | `actorId` (add) | NO |
| `TaskAssignedEvent` | `task` | `actorId` (add) | NO |
| `TaskClaimedEvent` | `task` | `actorId` (add, = userId of claimer) | NO |
| `TaskUnclaimedEvent` | `task` | `actorId` (add) | NO |
| `TaskDeletedEvent` | `task` | `actorId` (add) | NO |
| `CommentAddedEvent` | `task` | `authorId` (already present) | YES |

### Process Lifecycle (AUDT-02)
| Event | Entity Type | actorId Field | Already Has? |
|-------|-------------|---------------|-------------|
| `ProcessStartedEvent` | `process_instance` | `startedBy` | YES |
| `ProcessCompletedEvent` | `process_instance` | `actorId` = system | NO (system event) |
| `ProcessCancelledEvent` | `process_instance` | `cancelledBy` | YES |

### Auth Events (AUDT-02)
| Event | Entity Type | actorId Field | Already Has? |
|-------|-------------|---------------|-------------|
| `UserRegisteredEvent` | `user` | self (`userId`) | needs check |
| `UserActivatedEvent` | `user` | self (`userId`) | needs check |
| `PasswordChangedEvent` | `user` | self (`userId`) | needs check |

---

## Open Questions

1. **Should `organizationId` be added to TaskManager events?**
   - What we know: `TaskStatusChangedEvent`, `TaskAssignedEvent`, etc. do not carry `organizationId`. The audit API must scope results to organization.
   - What's unclear: Is it better to enrich events with `organizationId` OR load the task's `organizationId` in the async audit handler (one extra DB read)?
   - Recommendation: Load from task entity in the handler — keeps event payloads minimal and avoids a mass migration of event constructors. Acceptable because this is async (DB read cost is not on the HTTP path).

2. **One handler class per event type or one handler per module group?**
   - What we know: PHP Messenger dispatches by type — one `__invoke` per message type. Multiple `__invoke` on one class is not supported.
   - What's unclear: Creating 10+ handler classes vs 3 module-grouped handlers (task/process/auth groups).
   - Recommendation: Group by module (OnTaskAuditableEvents, OnProcessAuditableEvents, OnAuthAuditableEvents). Each class has one `__invoke` per event type it handles (separate method not possible — need separate classes). Actually: one class = one `__invoke` in Symfony Messenger. Use separate handler classes per event type OR use a single handler class registered multiple times (PHP does not support multiple `__invoke` on same class for different types by default). Use separate small handler classes per event — this is the Symfony Messenger convention.

3. **What constitutes `changes` JSONB for each event?**
   - What we know: The AUDT-02 requirement says "changes JSONB".
   - What's unclear: How granular? Just `{from: 'todo', to: 'done'}` for status change? Or include field names?
   - Recommendation: Keep it simple — `{field: 'status', from: 'todo', to: 'done'}` for mutations, `null` for create/delete events, process variables for process events.

---

## Sources

### Primary (HIGH confidence)
- Codebase — `src/Shared/Domain/DomainEvent.php`, `AggregateRoot.php`, `DispatchDomainEventsMiddleware.php` — event dispatch pipeline
- Codebase — `src/Notification/` entire module — template for Audit module structure
- Codebase — `src/Workflow/Application/Query/ListProcessInstances/ListProcessInstancesHandler.php` — DBAL pagination pattern
- Codebase — `src/Workflow/Domain/Event/ProcessStartedEvent.php`, `ProcessCancelledEvent.php` — actorId in event pattern
- Codebase — `backend/config/packages/messenger.yaml` — async transport routing
- Codebase — `frontend/src/modules/tasks/components/ProcessHistoryTimeline.vue` — PrimeVue Timeline usage
- Codebase — `.planning/STATE.md` — Phase 6 decision: clone QueryBuilder for COUNT before LIMIT/OFFSET; Phase 8 decisions: actorId in events, Security token null in async

### Secondary (MEDIUM confidence)
- Symfony Messenger docs (training data, Symfony 8.0) — multi-handler fan-out behavior confirmed by project's existing multi-handler event (TaskStatusChangedEvent already has both a Notification handler and Mercure publisher)

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all libraries already in project, versions confirmed from composer.json/package.json
- Architecture: HIGH — patterns derived from existing production code in same project (Notification module, ListProcessInstances, ProcessHistoryTimeline)
- Pitfalls: HIGH — actorId/Security issue explicitly documented in STATE.md; COUNT/LIMIT pitfall documented in STATE.md Phase 6 decisions; messenger.yaml routing from working code
- actorId field gaps: HIGH — confirmed by reading each domain event constructor

**Research date:** 2026-03-01
**Valid until:** 2026-06-01 (stable Symfony 8 + Doctrine ORM 3 — no breaking changes expected in 90 days)
