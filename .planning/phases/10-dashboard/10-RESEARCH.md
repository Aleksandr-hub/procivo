# Phase 10: Dashboard - Research

**Researched:** 2026-03-05
**Domain:** Dashboard UI (PrimeVue widgets + Chart.js charts) + Backend aggregate queries
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- 2x2 grid on desktop, vertical stack on mobile
- PrimeVue Card component for each widget
- Standard responsive breakpoints
- PrimeVue Aura theme colors throughout — no custom palette
- PrimeVue Chart component (Chart.js wrapper) for all charts
- Keep it clean and simple — no over-engineering

### Claude's Discretion
- Widget internal layout and information density
- Chart timeframes and drill-down behavior
- Activity feed entry format and grouping
- Empty states design
- Loading states and skeletons
- My Tasks card content (what metadata to show per task)
- Active Processes card content (status badges, progress indicators)
- Whether charts are interactive (tooltips, click-through) or static
- Data refresh strategy (on-mount vs polling vs Mercure)

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DASH-01 | My Tasks widget showing overdue, due today, and upcoming tasks — clickable cards | Existing `GET /organizations/{orgId}/tasks?assignee_id={employeeId}` API is reused; due-date bucketing done in frontend; navigate to `task-detail` route on click |
| DASH-02 | Active Processes widget showing processes where user is participant with status badges | `GET /organizations/{orgId}/process-instances?status=running` filtered to `started_by === authStore.user.id` on frontend; or backend adds `started_by` query param; `ProcessInstanceDTO.status` drives badges |
| DASH-03 | Charts: tasks by status (donut), completed over time (line), process completion rate (bar) | New backend endpoint `GET /api/v1/organizations/{orgId}/dashboard/stats` returning aggregated counts via DBAL; PrimeVue Chart component requires `chart.js` npm install |
| DASH-04 | Recent activity feed from audit log with entity links | Existing `GET /organizations/{orgId}/audit-log?limit=20` reused; AuditLogTimeline component already built in Phase 8; entity links built from `entity_type + entity_id` |
</phase_requirements>

---

## Summary

Phase 10 is primarily a frontend aggregation phase. All four requirements are served by combining **existing backend APIs** (tasks list, process instances list, audit log list) with **one new backend endpoint** for chart statistics. The new endpoint performs lightweight DBAL aggregate queries (COUNT GROUP BY) against `task_manager_tasks` and `workflow_process_instances_view` tables — no new tables or domain changes required.

The PrimeVue `Chart` component is a thin wrapper around Chart.js that is auto-imported by `PrimeVueResolver` in `vite.config.ts`, but `chart.js` itself is not yet in `package.json` and must be installed. The rest of the UI uses PrimeVue `Card`, `Skeleton`, `Tag`, `Badge`, and `ProgressSpinner` — all already auto-imported.

The dashboard page lives at `organizations/:orgId/dashboard` (new route), uses the organization context from `route.params.orgId`, and a new `dashboard.store.ts` coordinates parallel data fetching on mount. Data refresh strategy is on-mount only (no polling, no Mercure) — the dashboard is a snapshot view.

**Primary recommendation:** Build one new backend stats endpoint + one new DashboardPage.vue with four child widget components. Reuse existing API clients and the AuditLogTimeline component.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PrimeVue Chart | ^4.5.4 (already installed) | Wraps Chart.js for donut/line/bar charts | Project uses PrimeVue exclusively; Chart is official PrimeVue component |
| chart.js | ^4.x | Chart rendering engine (peer dependency of PrimeVue Chart) | PrimeVue Chart requires it as peer dep; must install separately |
| PrimeVue Card | ^4.5.4 (already installed) | Widget container (locked decision) | Project standard |
| PrimeVue Skeleton | ^4.5.4 (already installed) | Loading states | Already in components.d.ts |
| PrimeVue Tag | ^4.5.4 (already installed) | Status badges | Already in components.d.ts |
| Doctrine DBAL | Symfony 8 (already installed) | Backend aggregate queries | Pattern established in ListAuditLogHandler, ListProcessInstancesHandler |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PrimeVue ProgressSpinner | already installed | Widget loading state | While fetching data on mount |
| PrimeVue Badge | already installed | Unread/count indicators | Task counts per bucket |
| vue-i18n | ^12 (already installed) | Dashboard i18n keys | All UI text via `t()` |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| PrimeVue Chart (Chart.js) | Apache ECharts / Recharts | User locked decision: PrimeVue Chart only |
| on-mount fetch | polling every 30s | CONTEXT.md: Claude's discretion — on-mount is simpler and sufficient |
| Frontend due-date bucketing | Backend bucketing in stats endpoint | Frontend bucketing is simpler; tasks list already fetched for DASH-01 |

**Installation:**
```bash
npm install chart.js
```
(PrimeVue Chart component itself auto-imports via PrimeVueResolver — no explicit import needed in Vue files)

---

## Architecture Patterns

### Recommended Project Structure
```
frontend/src/modules/dashboard/
├── api/
│   └── dashboard.api.ts          # GET /organizations/{orgId}/dashboard/stats
├── components/
│   ├── MyTasksWidget.vue          # DASH-01: overdue/today/upcoming task cards
│   ├── ActiveProcessesWidget.vue  # DASH-02: running process instances for current user
│   ├── ChartsWidget.vue           # DASH-03: donut + line + bar in one card
│   └── RecentActivityWidget.vue   # DASH-04: wraps existing AuditLogTimeline
├── pages/
│   └── DashboardPage.vue          # 2x2 grid layout, coordinates widgets
├── stores/
│   └── dashboard.store.ts         # Pinia store with parallel fetch
└── types/
    └── dashboard.types.ts         # DashboardStatsDTO interface

backend/src/TaskManager/
├── Application/Query/GetDashboardStats/
│   ├── GetDashboardStatsQuery.php
│   └── GetDashboardStatsHandler.php  # DBAL aggregate queries
├── Presentation/Controller/
│   └── DashboardController.php        # GET /api/v1/organizations/{orgId}/dashboard/stats
```

### Pattern 1: Parallel Widget Data Fetch in Pinia Store
**What:** Dashboard store dispatches all widget data requests in parallel using `Promise.all`, not sequentially.
**When to use:** Always on dashboard mount — independent widgets should never wait for each other.

```typescript
// dashboard.store.ts
async function fetchAll(orgId: string, employeeId: string | null) {
  loading.value = true
  try {
    await Promise.all([
      fetchMyTasks(orgId, employeeId),
      fetchActiveProcesses(orgId),
      fetchStats(orgId),
      fetchRecentActivity(orgId),
    ])
  } finally {
    loading.value = false
  }
}
```

### Pattern 2: Due-Date Bucketing on Frontend
**What:** The existing `GET /tasks?assignee_id=...` returns all tasks for the current employee. The frontend computes overdue/today/upcoming buckets from `task.dueDate`.
**When to use:** For DASH-01. Avoids a new backend endpoint just for due-date filtering.

```typescript
// Computed from fetched tasks (non-done, non-cancelled)
const activeTasks = computed(() => tasks.filter(t => !['done', 'cancelled'].includes(t.status)))
const overdue = computed(() => activeTasks.value.filter(t => t.dueDate && isOverdue(t.dueDate)))
const today = computed(() => activeTasks.value.filter(t => t.dueDate && isToday(t.dueDate)))
const upcoming = computed(() => activeTasks.value.filter(t => t.dueDate && isFuture(t.dueDate) && !isToday(t.dueDate)))
// Tasks without dueDate shown in a 4th "No due date" bucket or simply as "upcoming" at end
```

The `isOverdue` utility already exists in `/frontend/src/shared/utils/date-format.ts`.

### Pattern 3: DBAL Aggregate Queries for Chart Stats
**What:** A new `GetDashboardStatsHandler` uses DBAL (not Doctrine ORM) to run GROUP BY queries against `task_manager_tasks` and `workflow_process_instances_view`. Pattern established in `ListAuditLogHandler` and `ListProcessInstancesHandler`.
**When to use:** Chart data (DASH-03). Aggregate counts are read-model queries — DBAL is lighter than ORM for these.

```php
// GetDashboardStatsHandler.php
// Tasks by status: SELECT status, COUNT(*) FROM task_manager_tasks WHERE organization_id = :orgId GROUP BY status
// Tasks completed last 30 days: SELECT DATE(updated_at) as day, COUNT(*) FROM task_manager_tasks WHERE organization_id = :orgId AND status = 'done' AND updated_at >= :since GROUP BY day ORDER BY day
// Process completion rate: SELECT status, COUNT(*) FROM workflow_process_instances_view WHERE organization_id = :orgId GROUP BY status
```

### Pattern 4: PrimeVue Chart Component Data Structure
**What:** PrimeVue `<Chart>` wraps Chart.js. Props: `type` (string), `data` (object), `options` (object).
**When to use:** All three charts in DASH-03.

```typescript
// Donut chart (tasks by status)
const donutData = computed(() => ({
  labels: ['Open', 'In Progress', 'Review', 'Done', 'Blocked'],
  datasets: [{
    data: [stats.open, stats.in_progress, stats.review, stats.done, stats.blocked],
    backgroundColor: [
      'var(--p-blue-400)',
      'var(--p-orange-400)',
      'var(--p-purple-400)',
      'var(--p-green-400)',
      'var(--p-red-400)',
    ],
  }],
}))

// Line chart (completed over time - last 30 days)
const lineData = computed(() => ({
  labels: stats.completedByDay.map(d => d.day),
  datasets: [{
    label: t('dashboard.completedTasks'),
    data: stats.completedByDay.map(d => d.count),
    borderColor: 'var(--p-primary-color)',
    tension: 0.4,
    fill: false,
  }],
}))

// Bar chart (process completion rate)
const barData = computed(() => ({
  labels: [t('workflow.instanceStatus_running'), t('workflow.instanceStatus_completed'), t('workflow.instanceStatus_cancelled')],
  datasets: [{
    label: t('dashboard.processes'),
    data: [stats.processRunning, stats.processCompleted, stats.processCancelled],
    backgroundColor: ['var(--p-blue-400)', 'var(--p-green-400)', 'var(--p-surface-400)'],
  }],
}))
```

```html
<Chart type="doughnut" :data="donutData" :options="donutOptions" style="max-height: 200px" />
<Chart type="line" :data="lineData" :options="lineOptions" style="max-height: 200px" />
<Chart type="bar" :data="barData" :options="barOptions" style="max-height: 200px" />
```

### Pattern 5: Active Processes — Filter by started_by on Frontend
**What:** For DASH-02, the existing `GET /process-instances?status=running` returns all running instances for the org. Filter client-side by `instance.started_by === authStore.user.id` to get "user's processes".
**When to use:** This is the simplest approach. Avoids adding a new `started_by` query param to the backend if the number of running instances per org is small (typical for BPM).

Alternative (if org has many running instances): Add `started_by` query param to `ListProcessInstancesQuery`. Given this is a pet project with limited data, frontend filter is sufficient.

### Pattern 6: Reuse AuditLogTimeline for DASH-04
**What:** The `AuditLogTimeline` component (built in Phase 8) already renders audit log entries. It accepts `orgId`, optional `entityType`, optional `entityId`. For the dashboard activity feed, pass only `orgId` (no entity filters) to show the org-wide last 20 entries.
**When to use:** DASH-04. Zero new component code needed.

```html
<!-- In RecentActivityWidget.vue -->
<AuditLogTimeline :orgId="orgId" :limit="20" />
```

The `AuditLogTimeline` component already calls `auditLogApi.list(orgId, { limit })` on mount.

### Pattern 7: Employee ID Resolution for My Tasks
**What:** The tasks API filters by `assignee_id` which is an **employee ID** (not user ID). The current user's employee ID must be resolved by looking up `employees.find(e => e.userId === authStore.user.id && e.status === 'active')`. This pattern is established in `TaskCreateDialog.vue`.
**When to use:** When fetching tasks for DASH-01.

```typescript
// In dashboard.store.ts or DashboardPage.vue setup
const empStore = useEmployeeStore() // already has employees for current org
const currentEmployeeId = computed(() =>
  empStore.employees.find(e => e.userId === authStore.user?.id && e.status === 'active')?.id ?? null
)
```

The employees list is already loaded by the org context; no extra API call needed.

### Pattern 8: 2x2 Responsive Grid
**What:** Two-column grid on desktop, single column on mobile using CSS Grid.
**When to use:** DashboardPage.vue top-level layout.

```css
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
```

### Anti-Patterns to Avoid
- **Sequential widget fetches:** Never `await fetchA(); await fetchB()` — always `Promise.all`.
- **Fetching full task detail for dashboard:** Use the list endpoint only; no need for `/tasks/{taskId}`.
- **Chart.js direct import instead of PrimeVue Chart:** User locked decision is PrimeVue Chart wrapper only.
- **ORM for aggregate queries:** Use DBAL directly (established pattern: ListAuditLogHandler, ListProcessInstancesHandler).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Chart rendering | Custom SVG/canvas charts | PrimeVue `<Chart>` + chart.js | Locked decision; Chart.js handles responsive, tooltips, animations |
| Activity feed component | New timeline component | `AuditLogTimeline` (built Phase 8) | Already exists with icon mapping, event labels, loading state |
| Date utility functions | `isOverdue()` | `@/shared/utils/date-format` already has `isOverdue()` | Already exists |
| isToday/isFuture | moment.js or date-fns | Inline `Date` comparison | No date library in project; simple logic is sufficient |
| Status badge styling | Custom CSS badges | PrimeVue `<Tag :severity="...">` | Already in components.d.ts, Aura theme styles it |

---

## Common Pitfalls

### Pitfall 1: chart.js Not Installed
**What goes wrong:** `<Chart>` renders nothing, console error "Cannot find module 'chart.js'".
**Why it happens:** PrimeVue Chart component imports Chart.js at runtime; it's a peer dependency not included in PrimeVue itself.
**How to avoid:** Run `npm install chart.js` before any Chart component usage.
**Warning signs:** Blank chart area, missing legend, or module resolution error in browser console.

### Pitfall 2: Employee ID vs User ID Confusion
**What goes wrong:** Tasks widget shows empty (no tasks found) even though user has assigned tasks.
**Why it happens:** `task_manager_tasks.assignee_id` stores the **employee ID** (UUID from `organization_employees` table), not the user ID. Passing `auth.user.id` to `assignee_id` query param returns empty results.
**How to avoid:** Always resolve `employeeId` from `empStore.employees.find(e => e.userId === auth.user.id)` before calling the tasks API. If `currentEmployeeId` is null (user not in org as employee), show "no tasks" empty state gracefully.
**Warning signs:** API call succeeds (200) but returns empty array despite user having tasks.

### Pitfall 3: started_by Is User ID (not Employee ID) in Process Instances
**What goes wrong:** Active Processes widget shows no processes for current user.
**Why it happens:** `workflow_process_instances_view.started_by` stores the **user ID** (from `CurrentUserProvider.getUserId()` in ProcessInstanceController). This is the opposite of tasks.
**How to avoid:** Filter processes by `instance.started_by === authStore.user.id` (user ID, not employee ID).

### Pitfall 4: Chart Options Missing for Dark Mode
**What goes wrong:** Chart text is invisible in dark mode (dark text on dark background).
**Why it happens:** Chart.js uses its own color configuration separate from PrimeVue's CSS variables.
**How to avoid:** Pass `options` with explicit colors using PrimeVue surface variables. Use `document.documentElement.style.getPropertyValue('--p-text-color')` or hardcode a neutral color. The project has dark mode (`.app-dark` class), so chart options should use theme-aware colors.

### Pitfall 5: DBAL Date Grouping PostgreSQL-Specific Syntax
**What goes wrong:** `DATE(updated_at)` works in MySQL but needs `updated_at::date` or `DATE(updated_at AT TIME ZONE 'UTC')` in PostgreSQL.
**Why it happens:** The project uses PostgreSQL 18. DBAL abstracts connections but raw SQL in queries is DB-specific.
**How to avoid:** Use `DATE(column)` — this works in PostgreSQL. Alternatively use `TO_CHAR(updated_at, 'YYYY-MM-DD')`. The project already uses PostgreSQL-specific `ILIKE` in other queries.

### Pitfall 6: AuditLogTimeline Actor Name Resolution
**What goes wrong:** Activity feed shows actor IDs instead of names.
**Why it happens:** `AuditLogDTO.actor_id` is a user ID string. The Phase 8 component renders it as-is without name resolution.
**How to avoid:** Check the existing `AuditLogTimeline` component — if it already shows actor IDs, this is known behavior. Don't add a user name lookup call for the dashboard widget (keep it simple). The entity link is more important than the actor name for dashboard context.

---

## Code Examples

Verified patterns from project codebase:

### Backend: New DashboardController (follows existing pattern)
```php
// Source: pattern from AuditLogController.php + TaskController.php
#[Route('/api/v1/organizations/{organizationId}/dashboard', name: 'api_v1_dashboard_')]
final readonly class DashboardController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {}

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');
        $stats = $this->queryBus->ask(new GetDashboardStatsQuery($organizationId));
        return new JsonResponse($stats);
    }
}
```

### Backend: GetDashboardStatsHandler (DBAL aggregate — follows ListAuditLogHandler pattern)
```php
// Source: pattern from ListAuditLogHandler.php
#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDashboardStatsHandler
{
    public function __construct(private Connection $connection) {}

    public function __invoke(GetDashboardStatsQuery $query): array
    {
        // Tasks by status
        $tasksByStatus = $this->connection->executeQuery(
            'SELECT status, COUNT(*) as cnt FROM task_manager_tasks WHERE organization_id = :orgId GROUP BY status',
            ['orgId' => $query->organizationId]
        )->fetchAllKeyValue();

        // Tasks completed last 30 days
        $since = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
        $completedByDay = $this->connection->executeQuery(
            'SELECT DATE(updated_at) as day, COUNT(*) as cnt FROM task_manager_tasks WHERE organization_id = :orgId AND status = \'done\' AND updated_at >= :since GROUP BY DATE(updated_at) ORDER BY day',
            ['orgId' => $query->organizationId, 'since' => $since]
        )->fetchAllAssociative();

        // Process instances by status
        $processesByStatus = $this->connection->executeQuery(
            'SELECT status, COUNT(*) as cnt FROM workflow_process_instances_view WHERE organization_id = :orgId GROUP BY status',
            ['orgId' => $query->organizationId]
        )->fetchAllKeyValue();

        return [
            'tasks_by_status' => $tasksByStatus,
            'tasks_completed_by_day' => $completedByDay,
            'processes_by_status' => $processesByStatus,
        ];
    }
}
```

### Frontend: Dashboard API client
```typescript
// dashboard.api.ts — follows audit-log.api.ts pattern
import httpClient from '@/shared/api/http-client'
import type { DashboardStatsDTO } from '@/modules/dashboard/types/dashboard.types'

export const dashboardApi = {
  stats(orgId: string): Promise<DashboardStatsDTO> {
    return httpClient.get(`/organizations/${orgId}/dashboard/stats`).then(r => r.data)
  },
}
```

### Frontend: Dashboard page route (add to router/index.ts)
```typescript
// In organizations/:orgId children block — follows existing child route pattern
{
  path: 'organizations/:orgId/dashboard',
  name: 'dashboard',
  component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
  props: true,
},
```

### Frontend: Chart options for dark-mode compatibility
```typescript
const chartOptions = {
  plugins: {
    legend: {
      labels: {
        color: 'var(--p-text-color)',
      },
    },
  },
  scales: { // only for line/bar
    x: { ticks: { color: 'var(--p-text-muted-color)' } },
    y: { ticks: { color: 'var(--p-text-muted-color)' } },
  },
}
```

### Frontend: Sidebar navigation item for Dashboard
```typescript
// In AppSidebar.vue — add before organizations entry or at top of org-scoped items
{
  label: t('nav.dashboard'),
  icon: 'pi pi-home',
  to: `/organizations/${orgId.value}/dashboard`,
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Dedicated stats module | Reuse existing query infrastructure (DBAL in query handlers) | Phase 8 established pattern | Dashboard stats handler is trivial to add |
| vue-chartjs wrapper | PrimeVue Chart (Chart.js wrapper) | PrimeVue 4.x | Consistent with project's PrimeVue-only policy |
| Separate employee resolution API | Resolved from already-loaded employees list | Phase 7 (employees in org store) | No extra API call |

**Deprecated/outdated:**
- vue-chartjs: Not used in this project. PrimeVue Chart is the chosen wrapper.
- chart.js v2/v3: Use chart.js v4.x (current, compatible with PrimeVue 4).

---

## Open Questions

1. **Should Dashboard be the default landing page after login?**
   - What we know: Current default route `''` goes to `OrganizationsPage`. Users need to select an org first before seeing the dashboard.
   - What's unclear: Whether to redirect from the org detail page to the dashboard, or add a "Dashboard" link in the sidebar and let users navigate to it.
   - Recommendation: Add dashboard as first item in the org-scoped sidebar menu. Do NOT change the root landing page — org selection must happen first. The route is `organizations/:orgId/dashboard`.

2. **How many days for "completed over time" line chart?**
   - What we know: CONTEXT.md leaves this to Claude's discretion.
   - Recommendation: Last 30 days. Generates 30 data points, meaningful trend visible, manageable query window.

3. **Active Processes — frontend filter vs backend `started_by` param?**
   - What we know: Running instances per org are typically few (under 100 in a pet project). `started_by` stores user ID.
   - Recommendation: Frontend filter `instances.filter(i => i.started_by === auth.user.id)` is sufficient. Avoids a backend change.

4. **What to show for tasks with no `dueDate`?**
   - Recommendation: Show as a separate 4th bucket "No due date" or append to "Upcoming". Keep it simple — one row labeled "No due date" at the bottom of the My Tasks widget.

---

## Validation Architecture

> Skipped — `workflow.nyquist_validation` not present in `.planning/config.json`.

---

## Sources

### Primary (HIGH confidence)
- Project codebase (`/backend/src/Audit/Presentation/Controller/AuditLogController.php`) — controller pattern
- Project codebase (`/backend/src/Audit/Application/Query/ListAuditLog/ListAuditLogHandler.php`) — DBAL aggregate pattern
- Project codebase (`/backend/src/Workflow/Application/Query/ListProcessInstances/ListProcessInstancesHandler.php`) — DBAL with view pattern
- Project codebase (`/backend/src/TaskManager/Infrastructure/Persistence/Doctrine/Mapping/Task.orm.xml`) — task table structure (`task_manager_tasks`, columns: `status`, `due_date`, `assignee_id`, `updated_at`, `organization_id`)
- Project codebase (`/frontend/src/modules/audit/components/`) — existing AuditLogTimeline component (reused for DASH-04)
- Project codebase (`/frontend/package.json`) — confirmed `chart.js` absent, must install
- Project codebase (`/frontend/vite.config.ts`) — PrimeVueResolver auto-imports `Chart` component; no manual import needed
- Project codebase (`/frontend/components.d.ts`) — Skeleton, Tag, Badge, ProgressSpinner all auto-imported
- Project codebase (`/frontend/src/shared/utils/date-format.ts`) — `isOverdue()` exists

### Secondary (MEDIUM confidence)
- [PrimeVue Chart docs](https://primevue.org/chart/) — Chart component requires `npm install chart.js` (peer dependency); props are `type`, `data`, `options`; verified via WebSearch cross-reference
- WebSearch 2025: "PrimeVue 4 Chart component requires chart.js installed separately as peer dependency" — confirmed by multiple sources

### Tertiary (LOW confidence)
- None.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all libraries already in project except chart.js; peer dep requirement confirmed
- Architecture: HIGH — all patterns verified from existing codebase (DBAL handler pattern, store pattern, auto-import setup)
- Pitfalls: HIGH for employee ID / user ID distinction (directly verified from source); MEDIUM for Chart.js dark mode (project pattern extrapolation)

**Research date:** 2026-03-05
**Valid until:** 2026-04-05 (stable libraries, no fast-moving dependencies)
