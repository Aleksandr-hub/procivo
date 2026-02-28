# Task Assignment & Routing — Technical Specification

**Status**: Draft
**Author**: AI + User collaboration
**Date**: 2026-02-27
**Based on**: Research of Camunda, Jira, Appian, Bitrix24, ProcessMaker, Bonita BPM, BPMN 2.0 spec

---

## 1. Problem Statement

Current task assignment in Procivo is primitive:
- Task node in designer has `assignee_type` (none/specific/role) but `role` is **never resolved** — stored as config, ignored at runtime
- No group pool / claim mechanism — tasks are either directly assigned or unassigned forever
- No hierarchy-based routing (manager, department head)
- No way for a user to "take" an unassigned task
- Assignee selection in task UI is a flat list of all employees — no scoping by role/department/permissions

## 2. Goal

Create a production-quality assignment system that:
1. **Covers all business scenarios** — from simple "assign to specific person" to complex "assign to all HR managers in this department, first one to claim gets it"
2. **Simple for users** — complex routing hidden behind intuitive UI. Non-technical users configure assignment via dropdowns, not expressions
3. **RBAC-aware** — respects existing permission scopes (own/subordinates/department/organization)
4. **Extensible** — new strategies can be added without architectural changes

## 3. Assignment Strategies

### 3.1 Strategy Types

| # | Strategy | Config in Designer | Runtime Resolution | Example |
|---|----------|-------------------|-------------------|---------|
| 1 | **Unassigned** | No additional config | Task created with `assigneeId = null`, no candidates | Ad-hoc task, anyone with access can assign |
| 2 | **Specific Employee** | Employee selector | `assigneeId = selectedEmployeeId` | "This stage always goes to John" |
| 3 | **Process Initiator** | No additional config | `assigneeId = processVariables['_task_creator_id']` | "Return to the person who started the process" |
| 4 | **Previous Performer** | Reference to which node | `assigneeId = whoCompletedNode(X)` | "Send back to who did the previous review" |
| 5 | **By Role (Group Pool)** | Role selector | Find all employees with role → candidates | "Any HR Manager can take this" |
| 6 | **By Department** | Department selector | Find all employees in dept → candidates | "Anyone in Finance department" |
| 7 | **By Manager** | Relative to whom (initiator/previous performer) | Find manager of user → `assigneeId` | "Escalate to manager of whoever submitted" |

### 3.2 Resolution Logic

When a workflow task node activates (`OnTaskNodeActivated`):

```
strategy = nodeConfig.assignment_strategy

CASE 'unassigned':
  → create task with assigneeId = null, candidates = []

CASE 'specific_user':
  → create task with assigneeId = nodeConfig.assignee_employee_id

CASE 'process_initiator':
  → create task with assigneeId = variables['_task_creator_id']

CASE 'previous_performer':
  → find who completed nodeConfig.reference_node_id in this process instance
  → create task with assigneeId = that employee

CASE 'by_role':
  → find all active employees with roleId = nodeConfig.assignee_role_id
  → IF count == 1: create task with assigneeId = that employee (auto-assign)
  → IF count > 1: create task with assigneeId = null, candidateRoleId = roleId
  → IF count == 0: create task with assigneeId = null (log warning)

CASE 'by_department':
  → find all active employees in departmentId = nodeConfig.assignee_department_id
  → IF count == 1: auto-assign
  → IF count > 1: create task with assigneeId = null, candidateDepartmentId = deptId
  → IF count == 0: assigneeId = null (log warning)

CASE 'by_manager':
  → resolve base user (initiator or previous performer)
  → find managerId of that user
  → create task with assigneeId = managerId
  → IF no manager: assigneeId = null (log warning)
```

### 3.3 Auto-Assignment Rules

When candidate pool has exactly **1 person** → auto-assign (no claim needed).
When candidate pool has **multiple people** → task goes to group queue (claim needed).
When candidate pool is **empty** → task created unassigned, warning logged.

## 4. Claim Mechanism

### 4.1 Task Assignment States

```
                ┌──────────────┐
                │  UNASSIGNED  │ ◄── no candidates, no assignee
                └──────────────┘

                ┌──────────────┐
          ┌────►│   AVAILABLE  │ ◄── has candidates, no assignee (group pool)
          │     └──────┬───────┘
          │            │ claim()
          │            ▼
          │     ┌──────────────┐
  unclaim()────►│   ASSIGNED   │ ◄── has assignee
                └──────────────┘
```

- **UNASSIGNED**: No one responsible, no candidate pool. Anyone with TASK_UPDATE permission can assign.
- **AVAILABLE**: Has candidate pool (role/department), waiting for someone to claim. All candidates see it in "Available" queue.
- **ASSIGNED**: Has a specific assignee. Only assignee sees it in "My Tasks".

### 4.2 API Endpoints

```
POST /api/v1/organizations/{orgId}/tasks/{taskId}/claim
  - Body: {} (empty — assigns to current user)
  - Validates: current user is in candidate pool (by role or department)
  - Sets assigneeId = currentUser.employeeId
  - Returns 409 if already claimed by someone else

POST /api/v1/organizations/{orgId}/tasks/{taskId}/unclaim
  - Body: { comment?: string }
  - Validates: current user is the assignee
  - Sets assigneeId = null
  - Task returns to AVAILABLE state

POST /api/v1/organizations/{orgId}/tasks/{taskId}/assign
  - Body: { employee_id: string }
  - Validates: current user has TASK_UPDATE permission
  - Validates: target employee is in candidate pool (if pool exists) OR user has org-level scope
  - Sets assigneeId = employee_id
```

### 4.3 Candidate Validation

When claiming or assigning a task with a candidate pool:
1. **By Role**: check `EmployeeRoleRepository.findByEmployeeIdAndRoleId(employeeId, candidateRoleId)` — must have the role
2. **By Department**: check `employee.departmentId == task.candidateDepartmentId` — must be in the department
3. **Override**: users with `TASK_UPDATE` permission at `organization` scope can assign to anyone regardless of pool

## 5. RBAC Integration

### 5.1 Who Can See Which Tasks

The existing `PermissionScope` system determines task visibility:

| Scope | Can see tasks assigned to... |
|-------|------------------------------|
| `own` | Only tasks assigned to self |
| `subordinates` | Self + direct reports' tasks |
| `subordinates_tree` | Self + all subordinates recursively |
| `department` | All tasks in own department |
| `department_tree` | All tasks in department + child departments |
| `organization` | All tasks in the organization |

### 5.2 Who Can See "Available" Tasks

Available (claimable) tasks visible to:
- **By Role pool**: only employees who have that role in the organization
- **By Department pool**: only employees in that department
- **Scoped further** by user's TASK_VIEW permission scope

### 5.3 Who Can Assign Others

- Only users with `TASK_UPDATE` permission
- Employee selector **filtered** by the user's scope:
  - `own` scope → can only assign to self
  - `department` scope → can assign to anyone in their department
  - `organization` scope → can assign to anyone

## 6. Data Model Changes

### 6.1 Task Entity — New Fields

```php
// Task.php — new nullable fields
private ?string $candidateRoleId = null;      // Role ID for group pool
private ?string $candidateDepartmentId = null; // Department ID for group pool
```

### 6.2 Task Node Config — Updated Structure

```json
{
  "assignment_strategy": "by_role",
  "assignee_employee_id": null,
  "assignee_role_id": "uuid-of-hr-manager-role",
  "assignee_department_id": null,
  "reference_node_id": null,
  "task_title_template": "Review application",
  "task_description_template": "...",
  "priority": "medium",
  "formFields": [...]
}
```

### 6.3 Doctrine Mapping

Add `candidate_role_id` and `candidate_department_id` columns to `tasks` table.

### 6.4 Domain Events

```php
TaskClaimedEvent(taskId, employeeId, claimedAt)
TaskUnclaimedEvent(taskId, employeeId, reason, unclaimedAt)
```

## 7. UI Design — Process Designer

### 7.1 Task Node Config Panel (Updated)

```
Stage Configuration
├── Stage Title        [____________]
├── Stage Description  [____________]
│
├── Assignment Strategy  [▼ Dropdown ]
│     ├── Unassigned
│     ├── Specific Employee  → shows employee selector
│     ├── Process Initiator
│     ├── Previous Performer → shows node selector
│     ├── By Role (Group)    → shows role selector
│     ├── By Department      → shows department selector
│     └── By Manager         → shows "of whom" selector
│
├── Priority            [▼ Dropdown ]
│
├── ─────── Divider ────────
└── Form Fields
    └── [FormFieldsBuilder]
```

### 7.2 Employee Selector

For "Specific Employee" strategy:
- Dropdown with search (autocomplete)
- Shows: "Name — Department — Position"
- Loads from employee API (filtered by user's scope)

### 7.3 Role Selector

For "By Role" strategy:
- Dropdown of organizational roles
- Shows count: "HR Manager (3 employees)"
- Loads from roles API

### 7.4 Department Selector

For "By Department" strategy:
- Tree dropdown (shows hierarchy)
- Shows count: "Finance (12 employees)"
- Loads from departments API

### 7.5 Node Selector

For "Previous Performer" strategy:
- Dropdown of Task nodes in current process
- Shows: "Node Name (Stage)"
- Only nodes before this one in the flow

## 8. UI Design — Task List & Detail

### 8.1 Task List Filters

```
[My Tasks (5)] [Available (3)] [All (12)]
```

- **My Tasks**: `assigneeId = currentUser.employeeId`
- **Available**: `assigneeId IS NULL AND (candidateRoleId IN myRoles OR candidateDepartmentId = myDeptId)`
- **All**: all tasks visible per RBAC scope

### 8.2 Task Detail — Assignment Section

**When task is AVAILABLE (group pool, no assignee):**
```
┌────────────────────────────────────┐
│ 👤 Unassigned                      │
│ [🙋 Assign to Me]  [👥 Assign to...] │
│                                    │
│ Candidates: HR Managers (3)        │
│   • Alice Johnson                  │
│   • Bob Smith                      │
│   • Carol Williams                 │
└────────────────────────────────────┘
```

**When task is ASSIGNED:**
```
┌────────────────────────────────────┐
│ 👤 Alice Johnson                   │
│ [↩️ Return to Queue] [📤 Reassign]  │
└────────────────────────────────────┘
```

**When task is UNASSIGNED (no pool):**
```
┌────────────────────────────────────┐
│ 👤 Unassigned                      │
│ [👥 Assign to...]                  │
└────────────────────────────────────┘
```

### 8.3 "Assign to..." Dialog

- Employee selector filtered by:
  1. Candidate pool (if exists) — only employees with matching role/department
  2. User's RBAC scope — only employees visible to current user
- Search by name
- Shows: Name, Department, Position

## 9. Implementation Phases

### Phase 1 — Core Assignment Engine (MVP)

**Goal**: Assignment strategies work in designer, claim works in task list.

**Backend:**
- [ ] Add `candidateRoleId`, `candidateDepartmentId` to Task entity + migration
- [ ] Create `AssignmentStrategy` enum: `unassigned`, `specific_user`, `process_initiator`, `by_role`, `by_department`
- [ ] Create `AssignmentResolver` service — resolves strategy → assigneeId + candidates
- [ ] Update `OnTaskNodeActivated` to use `AssignmentResolver`
- [ ] Add `ClaimTask`, `UnclaimTask` commands + handlers
- [ ] Add `AssignTask` command (with candidate pool validation)
- [ ] Add claim/unclaim/assign API endpoints
- [ ] Update `ListTasks` query to support "available" filter
- [ ] Domain events: `TaskClaimedEvent`, `TaskUnclaimedEvent`

**Frontend Designer:**
- [ ] Update `TaskNodeConfig.vue` — new assignment strategy dropdown + conditional fields
- [ ] Role selector component (loads org roles)
- [ ] Department selector component (loads dept tree)
- [ ] Employee selector with search

**Frontend Task List:**
- [ ] "Available" filter tab in `TaskListPanel.vue`
- [ ] "Assign to Me" button in `TaskDetailContent.vue`
- [ ] "Return to Queue" button
- [ ] Updated assignee display (shows pool info when unassigned)

**i18n:**
- [ ] All new keys for strategies, claim buttons, filters

### Phase 2 — Advanced Routing

**Goal**: Manager routing, previous performer, expression-based.

- [ ] `by_manager` strategy — resolve manager from org hierarchy
- [ ] `previous_performer` strategy — query process history for node completer
- [ ] Node selector in designer (for previous_performer reference)
- [ ] RBAC-filtered employee selector for "Assign to..." dialog
- [ ] Assignment audit trail (who assigned/claimed/unclaimed and when)

### Phase 3 — Escalation & SLA (Future)

- [ ] Escalation config per task node (like Appian's escalation tab)
- [ ] Timer-based auto-reassignment
- [ ] Notification on approaching deadline
- [ ] Skip absent users (integrate with employee status: OnLeave)

### Phase 4 — Smart Assignment (Future)

- [ ] Round-robin distribution
- [ ] Balanced workload (assign to least-loaded)
- [ ] AI-suggested assignment based on task content

### Phase 5 — Substitution & Authority Transfer (Future)

**Problem**: When an employee leaves, goes on vacation, or changes position — their responsibilities in running processes must transfer seamlessly. Tasks shouldn't hang, initiator references shouldn't break.

**Scenarios:**
1. **Temporary substitution** (vacation/sick leave): Employee A is away for 2 weeks. All their responsibilities temporarily go to Employee B. When A returns, everything reverts.
2. **Partial substitution**: Half of Employee A's processes go to Employee B, other half to Employee C (e.g., by department or process type).
3. **Permanent transfer** (dismissal/role change): Employee A leaves. All their active tasks, process initiator references, and candidate pool memberships transfer to Employee D permanently.
4. **Chained transfer**: Employee A → Employee B (temp), then Employee B → Employee D (permanent replacement). All of A's original responsibilities end up with D.

**Key requirements:**
- [ ] Substitution entity: `SubstitutionRule(fromEmployeeId, toEmployeeId, scope, startDate, endDate?, processDefinitionIds?)`
- [ ] Scope options: `all_processes`, `specific_processes`, `by_department`
- [ ] `SubstitutionResolver` service — checks active substitutions when resolving assignees
- [ ] Running process instances: re-resolve initiator references through substitution chain
- [ ] Task reassignment: bulk reassign active tasks from A to B
- [ ] Candidate pool: substitute inherits candidate eligibility (if A was in role "Manager", B gets those tasks)
- [ ] Revert mechanism: when substitution ends, pending tasks stay with substitute (no ping-pong)
- [ ] UI: "Substitutions" page in employee profile or organization settings
- [ ] Audit trail: all substitution changes logged
- [ ] Integration with Employee status (OnLeave → auto-activate substitution if configured)

**Competitor references:**
- Bitrix24: "Skip absent users" + "Backup users" per BP stage
- Camunda: No built-in substitution; handled via identity service plugins
- SAP: Full substitution management with date ranges and process scoping

**Complexity note:** This feature touches almost every module — needs careful planning as a separate spec.

## 10. Competitor Patterns Applied

| Competitor Pattern | Procivo Implementation |
|-------------------|----------------------|
| Camunda `candidateGroups` + claim | `candidateRoleId` + `ClaimTask` |
| Camunda `assignee="${expression}"` | Phase 2 expression-based strategy |
| Jira "Assign to me" | "Assign to Me" button on task detail |
| Appian auto-accept if pool=1 | Auto-assign when exactly 1 candidate |
| Appian escalation tab | Phase 3 escalation config |
| Bitrix24 "Any of listed" | By Role strategy with claim |
| Bitrix24 "Employee supervisors" | By Manager strategy |
| ProcessMaker Self-Service | Available filter + Claim API |
| Bonita Actor model | Organizational Role → candidate pool |
| BPMN 2.0 humanPerformer | Specific Employee strategy |
| BPMN 2.0 potentialOwner | By Role / By Department strategies |

## 11. Key Design Decisions

### Q1: Should "Available" tasks show the form before claiming?
**YES** (anti-pattern to hide details). User should preview the task to decide if they can do it. "Claim" button at the top of detail view.

### Q2: What happens when someone claims an already-claimed task?
**409 Conflict**. First claim wins. Second claimer sees a message "This task was already claimed by [Name]".

### Q3: Can unassigned tasks (no pool) be claimed?
**NO**. Claim only works for tasks with a candidate pool. Unassigned tasks (strategy=unassigned) require explicit assignment via "Assign to..." by a user with TASK_UPDATE permission.

### Q4: Should unclaiming reset form data?
**NO** (unlike Camunda). We preserve any partial work. The user's comment explains why they returned it, not why data was lost.

### Q5: How to handle "By Role" when role has 50+ employees?
Show paginated/searchable candidate list. Don't limit pool size. In practice, roles in a single department are rarely > 20 people.

### Q6: Should assignee selector always respect RBAC scopes?
**YES**. A user with `department` scope sees only their department's employees in the selector. `organization` scope sees everyone. This prevents information leakage.

## 12. Migration Notes

- Existing tasks continue working (null candidateRoleId/candidateDepartmentId = legacy behavior)
- Existing node configs with `assignee_type: 'none'` → maps to `assignment_strategy: 'unassigned'`
- Existing node configs with `assignee_type: 'specific'` → maps to `assignment_strategy: 'specific_user'`
- Existing node configs with `assignee_type: 'role'` → maps to `assignment_strategy: 'by_role'` (now actually resolved!)
