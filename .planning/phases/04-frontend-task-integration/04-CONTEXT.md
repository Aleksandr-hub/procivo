# Phase 4: Frontend Task Integration - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning
**Figma Prototype:** https://www.figma.com/make/cgshZil5qRJ31B5vWKsIkE/Analyze-system-file
**Design Screenshots:** docs/design/img.png — img_6.png

<domain>
## Phase Boundary

Users interact with workflow tasks through a polished UI: see dynamic forms per action, submit decisions, view process context with stepper and navigation, and claim pool tasks. This phase transforms the existing basic TaskDetailContent into a production-quality task management interface matching the Figma Make prototype.

**In scope (FEND-01 to FEND-11 + additions):**
- Task detail page restructuring (2-column layout with sidebar)
- Dynamic forms from form_schema with Zod validation
- Action dialogs with form fields, comment, and next assignment selector
- Pool task banner with claim/unclaim and candidate context
- Process Context Card (process name, stage, progress, navigation)
- My Path Stepper (completed + current steps, tooltip details)
- Process Data Card (process variables from previous stages) — pulled from v2
- Subtasks with checkboxes and progress — pulled from v2
- Runtime next assignment in action dialog — pulled from v2
- Task list process context badges
- Process history timeline tab
- "View Full Process" navigation to ProcessInstanceDetailPage

**Not in scope:**
- SLA indicators (FEND-V2-05) — sidebar placeholder only
- Real-time updates via Mercure (FEND-V2-03)
- Rich text editor (FEND-V2-11)
- "My Processes" process-centric view (see Deferred Ideas)

</domain>

<decisions>
## Implementation Decisions

### Task Detail Layout
- 2-column layout: main content (2/3) + sidebar (1/3), matching Figma prototype
- Sidebar hides on mobile (responsive breakpoint)
- Panel mode (embedded right of task list) shows compact view: no sidebar, no stepper, no Process Data Card
- Full page mode shows complete 2-column layout with all components
- "Expand" button in panel mode opens full page view

### Sidebar Content (all in v1)
- Assignment card (with pool task info)
- Status & Priority card
- Dates card (created, deadline)
- Time Tracking card (estimate vs spent, timer)
- Watchers card (avatar circles, subscribe/unsubscribe)
- Related Tasks card (mini task cards from same process)
- Creator card
- Labels card
- SLA card (placeholder for v2 — show deadline only)

### Action Buttons Placement
- Claude's Discretion — choose best pattern for PrimeVue (sticky header bar vs inline)
- Key requirement: action buttons must be easily accessible for workflow tasks

### My Path Stepper
- Custom component (not PrimeVue Stepper) matching Figma prototype visual style
- Shows only completed + current steps (no upcoming — unknown due to XOR gateways/branching)
- Stepper grows with each completed step
- Visual style: green checkmarks (completed), pulsing blue circle (current), green lines between completed, no dashed lines for upcoming
- Hover/click on completed step shows tooltip: who executed, when, which action
- Current step has no tooltip (already visible in context)
- Adaptive display: 2 modes only — full stepper (fits) or horizontal scroll (overflow). No modal for 20+
- Stepper placed in main content area, below Process Context Card

### Process Context Card
- Gradient card (purple/blue tones) matching Figma prototype
- 3 sections in row: process info (icon + name) | current stage (purple text) | progress + navigation
- Progress display: "Крок X" without total (no "/Y") because total is unknown with branching
- Progress bar proportional to completed steps (not percentage of total)
- "Переглянути процес" button opens ProcessInstanceDetailPage in new tab (target=_blank)
- "Наступний крок" hint removed (unknown with branching — only shows if backend provides it)
- Card placed in main content area, below pool task banner (if any), above stepper

### Process Data Card
- Key-value grid showing process variables from previous stages
- Each variable shows: label, value, source stage name
- "Показати все (N)" expandable if more than 8 variables
- Placed below stepper in main content area

### Subtasks
- Checklist with checkboxes, progress bar (X/Y completed), assignee avatars
- "+ Додати підзадачу" inline add
- Placed below Process Data Card (or Description if no process)

### Action Dialog UX
- Modal dialog (PrimeVue Dialog) matching Figma prototype
- Sections in order: dynamic form fields, comment (optional/required per action config), next assignment selector
- "Next Assignment" section shows:
  - Fixed assignment: read-only info block ("Finance Department — Стане pool task для вибору")
  - User choice: radio buttons for type (user/role/department) + Select dropdown for specific value + info hint
- Button styling: success variant for approve-type, destructive for reject-type, outline for others
- Submit button disabled until all required fields valid

### Form Validation
- Zod-based validation built from form_schema field definitions
- Inline errors under each field (red text + red border on input)
- Validation triggered on submit attempt
- Real-time validation on blur after first submit attempt
- Field types: text, textarea, number, date, select, checkbox

### After Action Submit
- Success toast notification
- Task detail shows "Етап завершено" message (existing behavior)
- Task disappears from active task list on next refresh
- User stays on task detail page (no redirect)

### Activity / Tabs
- Claude's Discretion — choose between:
  - Unified Activity Stream with filter chips (like prototype)
  - Keep existing separate tabs (Comments, Attachments, Assignments, Labels, History)
- Key: Process History tab must remain accessible for workflow tasks

### Task List Process Context
- Workflow tasks show purple icon (pi-sitemap) instead of blue (pi-list)
- Process context line: "TASK-002 · Затвердження бюджету → Розгляд керівниками"
- Pool Task badge for unclaimed pool tasks
- Priority and status badges on the right

### Claude's Discretion
- Action buttons placement pattern (sticky header vs inline in header section)
- Activity Stream vs separate tabs approach
- Exact spacing, typography, and color values (use PrimeVue design tokens)
- Loading skeleton designs
- Error state handling for API failures
- Empty states for sidebar cards without data
- Transition animations between states

</decisions>

<specifics>
## Specific Ideas

- "Match the Figma Make prototype as closely as possible" — user explicitly wants the full prototype implemented
- Figma Make source code available for reference: React+Tailwind components in `cgshZil5qRJ31B5vWKsIkE` — adapt to Vue 3 + PrimeVue 4
- Key components from Figma Make to adapt: TaskDetail.tsx, ProcessHeader.tsx, ProcessStepper.tsx, ActionDialog.tsx, TaskList.tsx, ProcessDataCard.tsx, SubtasksList.tsx, ActivityStream.tsx, WatchersCard.tsx, TimeTrackingCard.tsx, RelatedTasksCard.tsx, SLAIndicator.tsx
- Pool task banner uses gradient background (blue/purple) with overlapping avatar circles for candidates
- Action dialogs vary by complexity: simple (just comment), medium (fields + comment), complex (fields + comment + assignment selector)
- Process Context Card has subtle gradient background to visually distinguish from regular content cards
- Stepper uses pulsing animation on current step for visual emphasis

</specifics>

<deferred>
## Deferred Ideas

- **"Мої процеси" (My Processes) view** — Process-centric view where one row = one ProcessInstance. Shows process name, current stage, progress, assignee, status. Filters: "Мої ініційовані", "Я учасник", "Активні/Завершені". Lives as a tab alongside "Мої задачі" or separate menu item. Backend: needs new Query endpoint to filter ProcessInstances by userId. This is a new capability — its own phase.
- **Start Process Dialog** (FEND-V2-02) — Dialog with start form schema for launching new processes
- **Real-time updates** (FEND-V2-03) — Mercure SSE for live task updates
- **SLA indicators full implementation** (FEND-V2-05) — Circular progress with color coding, stage SLA hours, overdue alerts
- **Rich text description editor** (FEND-V2-11) — Markdown or ProseMirror for task descriptions

</deferred>

---

*Phase: 04-frontend-task-integration*
*Context gathered: 2026-02-28*
