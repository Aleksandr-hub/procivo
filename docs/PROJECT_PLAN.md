# Procivo — BPM Platform | Project Plan

> Business Process Management система з конфігурованим workflow engine,
> управлінням ресурсами (персонал, обладнання, нерухомість) та довідниками.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Symfony 8.0 + PHP 8.4 |
| Database | PostgreSQL 18 |
| Cache / Queue broker | Redis 8 |
| Message Broker | RabbitMQ 4.2 |
| Search Engine | Elasticsearch 9.3 |
| File Storage | LocalStack S3 (dev) / AWS S3 (prod) |
| Real-time | Mercure v2.10 |
| Frontend | Vue 3.5 + TypeScript 7 + Pinia 3 + Vite 7 |
| UI Kit | PrimeVue 4 |
| Workflow Visualizer | vue-flow |
| API types | REST (primary) + GraphQL + gRPC (inter-service) |
| API Docs | OpenAPI/Swagger (NelmioApiDocBundle) |
| Auth | Custom auth service (JWT + refresh tokens + OAuth2) |
| Containerization | Docker + Docker Compose |
| API Gateway | Nginx 1.28 (Phase 1-2), Traefik (Phase 3+) |
| CI/CD | GitHub Actions |
| Monitoring | Monolog (JSON) → ELK (Phase 1-2), Prometheus + Grafana (Phase 4) |
| Testing | PHPUnit 13, PHPStan 2.1 (level 6→8), PHP CS Fixer 3.94, Vitest (frontend) |

---

## Architecture Principles

- **Clean Architecture**: Domain → Application → Infrastructure → Presentation
- **DDD (Domain-Driven Design)**: Bounded contexts, aggregates, value objects
- **CQRS**: Separate read/write models (especially for Workflow Engine)
- **Event Sourcing**: Only for Workflow Engine (process execution history)
- **Modular Monolith → Microservices**: Start monolith, extract services gradually
- **Multi-tenant**: Organization-based data isolation

---

## Domain Model Overview

### Bounded Contexts (будущі мікросервіси)

```
┌─────────────────────────────────────────────────────────────────┐
│                        API Gateway (Nginx)                       │
├─────────┬───────────┬───────────┬──────────┬──────────┬─────────┤
│Identity │ OrgStruct │ TaskMgr   │ Workflow  │ Resource │ Notif   │
│Service  │ Service   │ Service   │ Engine    │ Registry │ Service │
│         │           │           │           │          │         │
│- Users  │- Org tree │- Tasks    │- Process  │- Staff   │- Email  │
│- Auth   │- Positions│- Boards   │  Definitions│- Equip │- Push   │
│- Roles  │- Teams    │- Comments │- Process  │- Real    │- In-app │
│- Perms  │- Hierarchy│- Attachm. │  Instances│  Estate  │- Webhook│
│         │           │- Labels   │- Nodes    │- Custom  │         │
│         │           │           │- Tokens   │  entities│         │
│         │           │           │- Events   │          │         │
├─────────┴───────────┴───────────┴──────────┴──────────┴─────────┤
│                    Shared: Directory Service                      │
│              (configurable catalogs, enums, statuses)             │
├──────────────────────────────────────────────────────────────────┤
│                    Search Service (Elasticsearch)                 │
├──────────────────────────────────────────────────────────────────┤
│                    File Service (MinIO)                           │
└──────────────────────────────────────────────────────────────────┘
```

### Entity Relationship (Core)

```
Organization (tenant)
 ├── OrganizationalUnit (tree: dept → division → team)
 │    └── Position (role in hierarchy)
 │         └── UserPosition (M2M: user ↔ position)
 ├── User (belongs to organization)
 ├── Resource (polymorphic: Equipment | RealEstate | Vehicle | Custom)
 │    ├── assigned to OrganizationalUnit
 │    └── linked to Tasks / ProcessInstances
 ├── Directory (custom catalog: equipment types, statuses, etc.)
 │    └── DirectoryItem (entries in catalog)
 ├── ProcessDefinition (BPMN-like workflow template)
 │    ├── Node (Start, End, Task, Gateway, Timer, SubProcess)
 │    └── Transition (connections between nodes with conditions)
 ├── ProcessInstance (running instance of a process)
 │    ├── Token (current position(s) in the graph)
 │    └── ProcessEvent (event-sourced history)
 ├── Task (work item, can be standalone or from workflow)
 │    ├── Comment
 │    ├── Attachment (→ MinIO)
 │    └── TaskAssignment (user | position | org unit)
 └── Board (Kanban/List view for tasks)
      └── Column
```

---

## Phases

---

### PHASE 0: Infrastructure & Foundation (Weeks 1-3)

**Goal**: Docker environment, Symfony project, CI/CD, basic project structure.

#### Week 1: Docker + Project Init ✅
- [x] Створити monorepo структуру (backend/, frontend/, docker/, docs/)
- [x] Docker Compose з сервісами: PHP-FPM, Nginx, PostgreSQL, Redis, RabbitMQ, Mercure, LocalStack S3, Mailpit
- [x] Symfony 8.0 skeleton install
- [x] Налаштувати Doctrine ORM 3.6 + PostgreSQL 18 connection
- [x] Налаштувати Redis 8 connection (FrameworkBundle cache + sessions)
- [x] Налаштувати Symfony Messenger + RabbitMQ 4.2 transport
- [x] Makefile з командами: `make up`, `make down`, `make bash`, `make test`, `make lint`, `make stan`
- [x] PHPUnit 13, PHPStan 2.1, PHP CS Fixer 3.94 встановлені та налаштовані

#### Week 2: Clean Architecture Setup ✅
- [x] Модульна структура: Shared, Identity, Organization, TaskManager, Workflow, Resource, Directory, Notification, Search
- [x] Кожен модуль: Domain/Application/Infrastructure/Presentation шари
- [x] CommandBus + QueryBus + EventBus через Symfony Messenger (3 окремі шини)
- [x] Базові Value Objects: Uuid (v7), Email, CreatedAt (readonly classes)
- [x] AggregateRoot з domain events (recordEvent/pullDomainEvents)
- [x] DomainEvent interface + DomainException hierarchy
- [x] CommandInterface, QueryInterface маркери для CQRS
- [x] Doctrine XML mappings налаштовані для кожного модуля
- [x] services.yaml — автовайрінг по модулях з виключенням Domain entities
- [x] 20 unit тестів (Value Objects + AggregateRoot) — всі зелені
- [x] PHPStan level 6 — 0 помилок, PHP CS Fixer — 0 помилок

#### Week 3: CI/CD + Dev Tools (частково)
- [x] GitHub repository init (https://github.com/Aleksandr-hub/procivo)
- [ ] GitHub Actions pipeline:
  - PHP lint (php-cs-fixer)
  - PHPStan level 6 (піднімемо до 8 поступово)
  - PHPUnit tests
  - Docker build test
- [x] Налаштувати PHPStan (level 6, done in Week 2)
- [x] Налаштувати PHP CS Fixer (Symfony rules, done in Week 2)
- [ ] Pre-commit hooks (GrumPHP або Captainhook)
- [ ] Створити .env.example та документацію по запуску

---

### PHASE 1: Identity + Organization Structure (Weeks 4-9)

**Goal**: Auth system, users, org hierarchy, RBAC — повноцінний identity module.

#### Week 4-5: Identity Module (Auth) ✅
- [x] Domain: User entity (id, email, password, status: pending/active/blocked)
- [ ] Domain: Role, Permission entities (→ Week 10-11: RBAC)
- [x] Domain events: UserRegistered, UserActivated, PasswordChanged
- [x] Application: RegisterUserCommand + Handler
- [x] Application: LoginQuery → JWT token pair (access + refresh)
- [x] Infrastructure: JWT token service (lexik/jwt-authentication-bundle v3.2)
- [x] Infrastructure: Refresh token rotation (stored in Redis)
- [x] REST API endpoints:
  - POST /api/v1/auth/register
  - POST /api/v1/auth/login
  - POST /api/v1/auth/refresh
  - POST /api/v1/auth/logout
  - GET  /api/v1/auth/me
  - PUT  /api/v1/auth/password (bonus: change password)
- [x] Middleware: JWT authentication guard (Lexik JWT + Symfony Security)
- [x] Middleware: Rate limiting (Redis-based) on auth endpoints
- [x] Unit tests: domain logic (User, UserId, HashedPassword, UserStatus, RegisterUserHandler, ChangePasswordHandler)
- [ ] Integration tests: auth flow (planned)
- [ ] OpenAPI docs для auth endpoints (NelmioApiDocBundle installed, config pending)

#### Week 6-7: Organization Structure Module ✅
- [x] Domain: Organization entity (tenant) — create, update, suspend, activate
- [x] Domain: Department entity (tree structure via materialized path + adjacency list)
- [x] Domain: Position entity (role in department)
- [x] Domain: Employee entity (user membership — hire, dismiss, changePosition, leave)
- [x] Domain ValueObjects: OrganizationId/Name/Slug/Status, DepartmentId/Code/Path/Status, PositionId/Name, EmployeeId/Number/Status (13 VOs)
- [x] Domain Events: OrganizationCreated, DepartmentCreated/Moved, PositionCreated, EmployeeHired/Dismissed (6 events)
- [x] Domain Exceptions: 8 domain-specific exceptions
- [x] Application: 13 Commands + 8 Queries with CQRS (create/update/delete org, dept, pos, emp)
- [x] Application: GetDepartmentTreeQuery — nested tree for frontend
- [x] Infrastructure: Materialized path + adjacency list for department hierarchy
- [x] Infrastructure: 4 Doctrine XML mappings + 4 repository implementations
- [x] REST API endpoints (4 controllers, 20 endpoints):
  - CRUD /api/v1/organizations (create, list, show, update, suspend)
  - CRUD /api/v1/organizations/{id}/departments (create, tree, show, update, move, delete)
  - CRUD /api/v1/organizations/{id}/positions (create, list, update, delete)
  - CRUD /api/v1/organizations/{id}/employees (hire, list, show, update, dismiss)
- [x] RBAC: OrganizationVoter (ORGANIZATION_VIEW / ORGANIZATION_MANAGE)
- [x] Application Port: CurrentUserProviderInterface + SecurityCurrentUserProvider
- [x] Tests: 82 total (37 domain + 7 application), PHPStan level 6 — 0 errors, CS Fixer clean
- [ ] Multi-tenancy: Doctrine tenant filter (deferred — explicit org checks in controllers for now)
- [ ] Seeder: demo organization (deferred to frontend phase)

#### Week 8-9: Frontend — Auth + Org Structure ✅
- [x] Vue 3 + TypeScript + Vite 7 init (npm create vue@latest)
- [x] Pinia Composition API stores: auth, organization, department, employee
- [x] Vue Router + auth guards (requiresAuth / guest meta)
- [x] PrimeVue 4 + custom Slate preset (definePreset) + light/dark theme toggle
- [x] Login / Register pages (PrimeVue forms, validation, error handling)
- [x] Dashboard layout (always-dark sidebar + topbar + content area)
- [x] Axios HTTP client with JWT interceptor + refresh token queue
- [x] Organization module: full CRUD (card grid, create dialog, detail page)
- [x] Department tree (PrimeVue Tree + detail panel with positions table)
- [x] Employee management (DataTable + filters + hire dialog with user search)
- [x] Theme system: useTheme composable, localStorage persistence, OS preference, anti-flash
- [x] Backend fix: MessengerQueryBus/CommandBus exception unwrapping (current() vs [0])
- [x] Backend: GET /api/v1/users?search= endpoint for user autocomplete
- [ ] Org chart visualization (vue-org-chart або vue-flow — deferred to Phase 2)
- [ ] Responsive design / mobile sidebar overlay

#### Week 10-11: Org Hierarchy + Invitations + RBAC + i18n
**Goal**: Reporting lines, invite flow, ролі/пермішени з hierarchy-aware scoping, мультимовність.

##### Organizational Hierarchy (Reporting Lines) ✅
- [x] Domain: Employee.managerId (nullable, self-referencing → Employee)
  - Explicit "reports to" зв'язок між працівниками
  - Доповнює implicit hierarchy через Department tree + Position.isHead
- [x] Domain events: EmployeeManagerChangedEvent
- [x] Application: SetManagerCommand + Handler — призначити/зняти керівника
- [x] Application: GetOrgChartQuery + Handler — hybrid дерево (departments + employees)
- [x] Application: GetSubordinatesQuery + Handler (direct + recursive via BFS)
- [x] Application: OrgChartNodeDTO — discriminated union (type: department | person)
- [x] REST API:
  - PUT  /api/v1/organizations/{id}/employees/{id}/manager (set manager)
  - GET  /api/v1/organizations/{id}/employees/{id}/subordinates?recursive=false
  - GET  /api/v1/organizations/{id}/employees/org-chart (full hybrid tree)
- [x] Frontend: Org Chart page (PrimeVue OrganizationChart)
  - Hybrid view: department tree as skeleton + employees inside each dept
  - Department nodes з "+" button → AddEmployeeToDeptDialog (Hire/Invite tabs)
  - Person nodes clickable → detail dialog (position, department, manager, direct reports)
  - Set Manager dialog (select from all employees, filter/search)
  - Remove Manager option
  - Auto-refresh after any modification
- [x] Hybrid approach:
  - Department tree → structural hierarchy (де працює)
  - Employee.managerId → management hierarchy (кому підпорядковується)
  - Position.isHead → highlighted in org chart, sorted first
- [ ] Drag & drop для переміщення (change manager) — deferred
- [ ] Zoom / pan / collapse levels — deferred
- [ ] Filter by department — deferred

##### Invitation System (Invite Flow) ✅
- [x] Domain: Invitation entity (Organization module)
  - id, organizationId, email, token, status (pending/accepted/expired/cancelled)
  - departmentId, positionId (pre-assign department + position)
  - employeeNumber (pre-assign)
  - expiresAt, invitedBy (userId), acceptedAt
- [x] Domain: InvitationToken value object (secure random, 64-char hex, expirable)
- [x] Domain events: InvitationCreatedEvent, InvitationAcceptedEvent, InvitationCancelledEvent
- [x] Application: InviteUserCommand + Handler → створює Invitation + надсилає email
- [x] Application: AcceptInvitationCommand + Handler → приймає запрошення, створює Employee
  - Auto-assign: department, position, employee number — з Invitation
- [x] Application: CancelInvitationCommand + Handler
- [x] Application: ListInvitationsQuery + Handler
- [x] Infrastructure: Email template (Symfony Mailer → Mailpit в dev)
- [x] REST API:
  - POST /api/v1/organizations/{id}/invitations (invite by email)
  - GET  /api/v1/organizations/{id}/invitations (list pending)
  - POST /api/v1/invitations/{token}/accept
  - DELETE /api/v1/organizations/{id}/invitations/{id} (cancel)
- [x] Frontend: InviteDialog (email + department + position + employee number)
- [x] Frontend: Accept invitation page (/invitations/{token}/accept)
- [x] Frontend: Invitation list in org detail page
- [ ] Manager pre-assign in invitation — deferred (set after employee joins)

##### RBAC (Roles + Permissions + Hierarchy-Aware Scoping) ✅
- [x] Дослідження: Symfony Voters vs Casbin vs custom policy engine
  - Рішення: Symfony Voters + Role/Permission entities + PermissionResolver
- [x] Domain: Role entity (per organization, not global)
  - id, organizationId, name, description, isSystem (non-deletable), hierarchy (0=highest)
  - Default system roles: Admin (hierarchy 0), Manager (50), Employee (100)
- [x] Domain: Permission entity (granular access)
  - resource (employee, department, position, role, invitation, organization)
  - action (view, create, update, delete, manage)
  - **scope** — hierarchy-aware data visibility:
    | Scope | Бачить |
    |-------|--------|
    | `own` | Тільки свої дані |
    | `subordinates` | Свої + прямих підлеглих |
    | `subordinates_tree` | Свої + всіх підлеглих рекурсивно |
    | `department` | Всіх у своєму відділі |
    | `department_tree` | Свій відділ + дочірні відділи |
    | `organization` | Все в організації |
- [x] Domain: Permission belongs to Role (role_id + resource + action unique)
- [x] Domain: EmployeeRole (M2M: employee ↔ role, per organization)
- [x] Domain: Value Objects — RoleId, PermissionId, PermissionScope, PermissionResource, PermissionAction enums
- [x] Domain: Events — RoleCreated, RoleAssignedToEmployee, RoleRevokedFromEmployee, PermissionGranted, PermissionRevoked
- [x] Domain: Exceptions — RoleNotFound, RoleAlreadyExists, CannotDeleteSystemRole, PermissionNotFound, PermissionAlreadyExists
- [x] Application: PermissionResolver (PermissionResolverInterface) — визначає видимі записи:
  - hasPermission(userId, orgId, resource, action): bool
  - resolveScope(userId, orgId, resource, action): ?PermissionScope
  - resolveVisibleEmployeeIds(userId, orgId, resource, action): array
  - Uses Employee.managerId for subordinates scopes, Department.path for department_tree
- [x] Application: SeedDefaultRoles — автосідинг Admin/Manager/Employee з permissions при створенні org
- [x] Application: Event handlers — SeedDefaultRolesOnOrganizationCreated, AssignDefaultRoleOnEmployeeHired
- [x] Application: CQRS — 7 Commands (CreateRole, UpdateRole, DeleteRole, AssignRole, RevokeRole, GrantPermission, RevokePermission)
- [x] Application: CQRS — 4 Queries (ListRoles, GetRole, GetEmployeeRoles, GetMyPermissions)
- [x] Infrastructure: PermissionVoter (RESOURCE_ACTION format, e.g. EMPLOYEE_VIEW) + owner bypass
- [x] Infrastructure: Doctrine XML mappings (3 tables: organization_roles, organization_permissions, organization_employee_roles)
- [x] Infrastructure: Migration + Doctrine repositories
- [x] REST API:
  - CRUD /api/v1/organizations/{id}/roles
  - POST/DELETE /api/v1/organizations/{id}/roles/{id}/permissions
  - GET/POST/DELETE /api/v1/organizations/{id}/employees/{id}/roles
  - GET /api/v1/organizations/{id}/my-permissions
- [x] Frontend: Role management page (RolesPage — DataTable, create/edit/delete roles)
- [x] Frontend: RoleDetailPage (permissions table, add/revoke with descriptions in dropdowns)
- [x] Frontend: permission.store (can(), getScope(), isOwner) + role.store
- [x] Frontend: Router + sidebar navigation
- [x] Console: app:seed-roles command
- [x] Refactor controllers to use granular RESOURCE_ACTION voters (EMPLOYEE_CREATE, DEPARTMENT_VIEW, etc.)
- [x] Global DomainExceptionListener — replaces per-controller translateException() duplication
- [x] OrganizationAuthorizer service — replaces per-controller authorize() duplication
- [x] DomainException.getStatusCode() — each exception declares its HTTP status (404, 409, 401, etc.)
- [ ] Doctrine query filters for automatic scope enforcement (deferred)
- [ ] Permission matrix UI (role × resource × action × scope) (deferred — future UX improvement)
- [ ] Permission presets / templates for quick role setup (deferred — future UX improvement)

##### Internationalization (i18n) ✅
- [x] vue-i18n setup (Composition API, legacy: false)
- [x] Locale files: en (default), uk — ~200+ keys organized by module
- [x] Frontend translations: all 18 Vue files (pages, components, sidebar, topbar)
- [x] Backend: Symfony Translation component + TranslatableExceptionInterface
- [x] Backend: 21 domain exceptions implement TranslatableExceptionInterface
- [x] Backend: LocaleListener (Accept-Language header → request locale)
- [x] Backend: translation files (messages.en.json, messages.uk.json)
- [x] Frontend: Accept-Language header in HTTP client interceptor
- [x] Language selector in topbar (EN/UK toggle, persist in localStorage)
- [x] PrimeVue locale sync (paginator, calendar labels)
- [x] useLocale composable (singleton, watch, document.lang sync)
- [ ] Date/number formatting per locale (Intl API) — deferred
- [ ] Additional locales: de, fr, pl, es — deferred

##### Architecture Diagrams
- [ ] Frontend: Org Chart page (see Organizational Hierarchy above)
- [ ] docs/diagrams/ — Mermaid-based technical diagrams:
  - ER diagram (database schema)
  - Module dependency graph (bounded contexts)
  - Sequence diagrams: login, invite, hire, RBAC check
  - Deployment diagram (Docker services)
- [ ] docs/ARCHITECTURE.md update

---

### PHASE 2: Task Manager (Weeks 12-17)

**Goal**: Повноцінний task management — boards, tasks, comments, файли.

#### Week 12-13: Task Domain
- [ ] Domain: Task entity (title, description, status, priority, due_date, estimated_hours)
- [ ] Domain: Board entity, Column entity (Kanban)
- [ ] Domain: Comment entity (threaded)
- [ ] Domain: Label entity (color-coded tags)
- [ ] Domain: TaskAssignment — assignee може бути User | Position | OrgUnit
- [ ] Domain events: TaskCreated, TaskStatusChanged, TaskAssigned, CommentAdded
- [ ] Domain: Task status machine (Symfony Workflow component for task states):
  ```
  draft → open → in_progress → review → done
                  ↓                        ↓
               blocked                  reopened → in_progress
  ```
- [ ] Application: CQRS — окремі Command та Query handlers
- [ ] Application: Task filters (by status, assignee, label, due date)

#### Week 14: Task API + File uploads
- [ ] REST API endpoints:
  - CRUD /api/v1/boards
  - CRUD /api/v1/boards/{id}/columns (ordering)
  - CRUD /api/v1/tasks
  - POST /api/v1/tasks/{id}/assign
  - POST /api/v1/tasks/{id}/transition (change status)
  - CRUD /api/v1/tasks/{id}/comments
  - GET  /api/v1/tasks/{id}/history (audit log)
- [ ] File upload service → MinIO:
  - POST /api/v1/tasks/{id}/attachments
  - GET  /api/v1/attachments/{id}/download (pre-signed URL)
- [ ] Task search with filters (PostgreSQL full-text as starting point)
- [ ] OpenAPI docs
- [ ] Tests

#### Week 15: RabbitMQ Integration
- [ ] Symfony Messenger з RabbitMQ transport
- [ ] Async handlers:
  - TaskCreated → send notification
  - TaskAssigned → send notification
  - CommentAdded → send notification
  - TaskStatusChanged → log + notify
- [ ] Dead letter queue (DLQ) для failed messages
- [ ] Retry policy (3 attempts with exponential backoff)
- [ ] Supervisor config для workers
- [ ] Monitoring: RabbitMQ management UI в Docker

#### Week 16-17: Frontend — Task Manager
- [ ] Kanban board (drag & drop columns and cards, PrimeVue + custom)
- [ ] Task detail view (slide-over panel або full page):
  - Editable fields
  - Status transitions (buttons based on available transitions)
  - Comments thread
  - File attachments (upload + preview)
  - Activity history timeline
  - Assignment (search user/position/unit)
- [ ] Board settings (add/remove/reorder columns)
- [ ] Task list view (alternative to Kanban — table with filters)
- [ ] Real-time updates via Mercure (task changes reflect immediately)

---

### PHASE 3: Workflow Engine — Core (Weeks 18-25)

**Goal**: Конфігурований BPMN-подібний workflow engine з візуальним редактором.

#### Week 18-19: Workflow Domain Model
- [ ] Domain: ProcessDefinition (template of a business process)
- [ ] Domain: Node entity (abstract) з типами:
  - StartNode
  - EndNode
  - TaskNode (creates a Task when activated)
  - ExclusiveGateway (if/else — one path)
  - ParallelGateway (fork/join — all paths)
  - InclusiveGateway (one or more paths based on conditions)
  - TimerNode (wait for duration or until datetime)
  - SubProcessNode (nested process)
  - WebhookNode (call external URL)
  - NotificationNode (send notification)
- [ ] Domain: Transition (source_node → target_node + condition expression)
- [ ] Domain: ConditionExpression value object (простий expression language)
- [ ] Domain: ProcessDefinitionVersion (versioning of process definitions)
- [ ] Validation: graph must have exactly 1 start, at least 1 end, no orphan nodes

#### Week 20-21: Process Execution Engine
- [ ] Domain: ProcessInstance (running process)
- [ ] Domain: Token (position marker in the process graph)
  - Token рухається по графу
  - На ParallelGateway (fork): один токен → кілька токенів
  - На ParallelGateway (join): чекаємо всі токени → один токен
- [ ] Event Sourcing для ProcessInstance:
  - ProcessStarted
  - TokenMoved
  - TokenCreated (parallel fork)
  - TokenConsumed (parallel join)
  - TaskNodeActivated → створює Task
  - GatewayEvaluated (з результатом condition)
  - TimerScheduled / TimerFired
  - ProcessCompleted
  - ProcessCancelled
- [ ] CQRS: Write model (event store) + Read model (projected state)
- [ ] Expression evaluator для conditions (Symfony ExpressionLanguage)
- [ ] Timer service: schedule timer events (cron or delayed message via RabbitMQ)

#### Week 22-23: Workflow API + Integration
- [ ] REST API:
  - CRUD /api/v1/process-definitions
  - POST /api/v1/process-definitions/{id}/publish (version + activate)
  - POST /api/v1/process-instances (start process)
  - GET  /api/v1/process-instances/{id} (current state + tokens)
  - GET  /api/v1/process-instances/{id}/history (event stream)
  - POST /api/v1/process-instances/{id}/cancel
- [ ] Integration з Task Manager:
  - TaskNode activation → creates Task with context
  - Task completion → advances token
  - Task assigns based on workflow config (role/position/specific user)
- [ ] Integration з Org Structure:
  - Workflow can reference OrgUnit, Position for task assignment
  - "Assign to: Head of {initiator's department}"
- [ ] Webhook Node implementation:
  - HTTP call to external URL with process context
  - Retry on failure
  - Store response in process variables
- [ ] Tests: unit (domain), integration (full process execution)

#### Week 24-25: Frontend — Workflow Designer
- [ ] vue-flow based visual editor:
  - Drag nodes from palette onto canvas
  - Connect nodes with edges (transitions)
  - Configure each node (click → property panel)
  - Condition editor for gateways
  - Assignment config for task nodes
  - Timer configuration
- [ ] Process definition list + management
- [ ] Process instance monitoring:
  - Visual view of running process (highlighted current position)
  - Token positions shown on the graph
  - Event history timeline
- [ ] Start process form (if process has input variables)
- [ ] "My tasks from workflows" — inbox of assigned workflow tasks

---

### PHASE 4: Resource Registry + Directories (Weeks 26-31)

**Goal**: Універсальний реєстр ресурсів та конфігуровані довідники.

#### Week 26-27: Directory Service (Configurable Catalogs)
- [ ] Domain: Directory (catalog definition: name, slug, fields config)
- [ ] Domain: DirectoryField (field definitions: text, number, date, enum, relation)
- [ ] Domain: DirectoryItem (entry in catalog with dynamic field values)
- [ ] EAV (Entity-Attribute-Value) або JSONB для dynamic fields
  - JSONB (PostgreSQL) рекомендовано — простіше, швидше
- [ ] REST API:
  - CRUD /api/v1/directories (manage catalogs)
  - CRUD /api/v1/directories/{id}/fields (manage fields)
  - CRUD /api/v1/directories/{id}/items (manage entries)
  - GET  /api/v1/directories/{id}/items?filter[field]=value
- [ ] Seed: стандартні довідники (Equipment Types, Priority Levels, Regions)

#### Week 28-29: Resource Registry
- [ ] Domain: Resource (base entity)
- [ ] Domain: ResourceType (Equipment, RealEstate, Vehicle, Custom)
- [ ] Domain: ResourceAttribute (dynamic attributes per resource type → JSONB)
- [ ] Domain: ResourceAssignment (resource ↔ org unit, resource ↔ task, resource ↔ process)
- [ ] Domain events: ResourceCreated, ResourceAssigned, ResourceMoved
- [ ] Integration з Directories (resource type → directory for attributes)
- [ ] Integration з Workflow: resource can be input/context of a process
- [ ] REST API:
  - CRUD /api/v1/resources
  - GET  /api/v1/resources?type=equipment&filter[status]=active
  - POST /api/v1/resources/{id}/assign
  - GET  /api/v1/resources/{id}/history
- [ ] File attachments for resources (photos, documents → MinIO)

#### Week 30-31: Frontend — Resources + Directories
- [ ] Directory manager:
  - Create/edit catalogs
  - Field configurator (drag to reorder, set types, validations)
  - Item management (dynamic form generated from field config)
- [ ] Resource registry UI:
  - Resource list with filters (by type, status, org unit)
  - Resource detail page (attributes, photos, assignment history)
  - Assign resource to org unit / task / process (modal)
  - Map view for real estate (Leaflet.js — optional)
- [ ] Integration в workflow designer: "Select resource" node config

---

### PHASE 5: Search + Notifications + Webhooks (Weeks 32-37)

**Goal**: Elasticsearch full-text search, notification system, webhooks.

#### Week 32-33: Elasticsearch Integration
- [ ] Elasticsearch indices:
  - tasks (title, description, comments, labels)
  - resources (name, attributes, type)
  - users (name, email, position)
  - process_definitions (name, description)
  - directory_items (all fields)
- [ ] Indexing via RabbitMQ (async):
  - Entity changed → publish event → consumer updates ES index
- [ ] Search API:
  - GET /api/v1/search?q=...&type=tasks,resources
  - Faceted search (filter by type, status, org unit)
  - Autocomplete / suggestions
- [ ] Frontend: global search bar з результатами по всіх сутностях

#### Week 34-35: Notification Service
- [ ] Domain: Notification entity (type, channel, recipient, payload, status)
- [ ] Domain: NotificationPreference (user preferences per channel)
- [ ] Channels:
  - In-app (stored in DB, delivered via Mercure real-time)
  - Email (Symfony Mailer + queue via RabbitMQ)
  - Webhook (HTTP POST to configured URL)
- [ ] Notification templates (Twig-based)
- [ ] REST API:
  - GET  /api/v1/notifications (list for current user)
  - POST /api/v1/notifications/{id}/read
  - POST /api/v1/notifications/read-all
  - CRUD /api/v1/notification-preferences
- [ ] Frontend: notification bell + dropdown + notification center page

#### Week 36-37: Webhook System
- [ ] Domain: WebhookEndpoint (URL, secret, events subscribed)
- [ ] Domain: WebhookDelivery (log of each delivery attempt)
- [ ] Mechanism:
  - Event occurs → check subscribed webhooks → queue delivery
  - HMAC signature for payload verification
  - Retry policy (3 attempts, exponential backoff)
  - Delivery log with status, response code, response body
- [ ] REST API:
  - CRUD /api/v1/webhooks
  - GET  /api/v1/webhooks/{id}/deliveries (delivery log)
  - POST /api/v1/webhooks/{id}/test (send test event)
- [ ] Events available for webhooks:
  - task.created, task.completed, task.assigned
  - process.started, process.completed
  - resource.created, resource.assigned
- [ ] Frontend: webhook management page (URL, secret, event selection, delivery log)

---

### PHASE 6: GraphQL + gRPC + API Maturity (Weeks 38-41)

**Goal**: Додаткові API protocols, API versioning, rate limiting.

#### Week 38-39: GraphQL API
- [ ] Install webonyx/graphql-php або overblog/GraphQLBundle
- [ ] GraphQL schema для основних сутностей:
  - Query: tasks, resources, users, orgTree, processInstances
  - Mutation: createTask, updateTask, assignTask, startProcess
  - Subscription: taskUpdated, notificationReceived (via Mercure)
- [ ] Nested queries (key advantage):
  ```graphql
  query {
    task(id: "...") {
      title
      assignee { name, position { title, orgUnit { name } } }
      comments { author { name }, text, createdAt }
      processInstance { definition { name }, currentNodes { name } }
    }
  }
  ```
- [ ] DataLoader pattern (prevent N+1 queries)
- [ ] Rate limiting per query complexity
- [ ] GraphQL Playground UI

#### Week 40-41: gRPC (Inter-service Communication)
- [ ] Install roadrunner-server/grpc або spiral/grpc
- [ ] Proto definitions (.proto files):
  ```protobuf
  service TaskService {
    rpc GetTask (GetTaskRequest) returns (TaskResponse);
    rpc CompleteTask (CompleteTaskRequest) returns (TaskResponse);
  }
  service WorkflowService {
    rpc StartProcess (StartProcessRequest) returns (ProcessInstanceResponse);
    rpc GetTokenPositions (GetTokensRequest) returns (TokenListResponse);
  }
  service IdentityService {
    rpc ValidateToken (TokenRequest) returns (UserResponse);
    rpc GetUsersByOrgUnit (OrgUnitRequest) returns (UserListResponse);
  }
  ```
- [ ] gRPC server implementation
- [ ] gRPC client for inter-module communication
- [ ] Протестувати: Workflow Engine → TaskService.CompleteTask via gRPC
- [ ] Health check gRPC service

---

### PHASE 7: Microservices Extraction (Weeks 42-47)

**Goal**: Розділити модульний моноліт на реальні мікросервіси.

#### Week 42-43: Notification Service extraction
- [ ] Окремий Symfony проект для Notification Service
- [ ] Окремий Docker container + окрема БД
- [ ] Комунікація: RabbitMQ (async) + gRPC (sync)
- [ ] Окремий CI/CD pipeline
- [ ] API Gateway routing (Traefik)

#### Week 44-45: Search Service extraction
- [ ] Окремий lightweight сервіс (можна навіть не Symfony — plain PHP або Go для досвіду)
- [ ] Consumes events from RabbitMQ → indexes in Elasticsearch
- [ ] Exposes search gRPC + REST API
- [ ] Traefik routing

#### Week 46-47: Infrastructure hardening
- [ ] Traefik як API Gateway (замість Nginx для routing)
- [ ] Service discovery via Docker labels
- [ ] Health checks для всіх сервісів
- [ ] Centralized logging (всі сервіси → ELK)
- [ ] Prometheus metrics endpoint в кожному сервісі
- [ ] Grafana dashboards:
  - Request rate / latency per service
  - RabbitMQ queue depth
  - Error rates
  - Active process instances
- [ ] Docker Compose profiles (dev, test, prod)
- [ ] README з повною архітектурною діаграмою

---

### PHASE 8: Frontend Polish + Advanced Features (Weeks 48-54)

**Goal**: Фінальна полірування, advanced features, production readiness.

#### Week 48-49: Dashboard + Analytics
- [ ] Dashboard page:
  - My tasks (overdue, today, upcoming)
  - Active processes (my involvement)
  - Team workload overview
  - Recent activity feed
- [ ] Charts (Chart.js або PrimeVue Charts):
  - Tasks by status (pie)
  - Tasks completed over time (line)
  - Process cycle time (bar)
  - Resource utilization (bar)

#### Week 50-51: Advanced Workflow Features
- [ ] Process templates (pre-built common workflows):
  - Approval flow (request → approve/reject)
  - Inspection flow (schedule → inspect → report → approve)
  - Onboarding flow (HR → IT → Manager → Employee)
- [ ] Process variables (input/output data flowing through the process)
- [ ] Form builder for TaskNodes (custom forms per workflow step)
- [ ] Sub-process support (process within a process)
- [ ] Process analytics (avg duration, bottleneck nodes)

#### Week 52-53: OAuth2 + External Integrations
- [ ] OAuth2 Provider (allow external apps to authenticate)
- [ ] OAuth2 Client (login via Google, GitHub)
- [ ] API keys for external integrations
- [ ] Telegram bot notification channel (optional)
- [ ] Export: CSV, PDF reports

#### Week 54: Final Polish
- [ ] Performance audit (N+1 queries, slow endpoints)
- [ ] Security audit (OWASP top 10 checklist)
- [ ] Mobile responsive check
- [ ] README + architecture docs + ADR documents
- [ ] Demo data seeder (full company with processes, tasks, resources)
- [ ] Recording demo video / screenshots for portfolio

---

## Folder Structure (Final)

```
procivo/
├── backend/
│   ├── src/
│   │   ├── Shared/              # Shared kernel
│   │   ├── Identity/            # Auth, Users, Roles
│   │   ├── Organization/        # Org structure, hierarchy
│   │   ├── TaskManager/         # Tasks, Boards, Comments
│   │   ├── Workflow/            # Process engine (Event Sourced)
│   │   ├── Resource/            # Equipment, Real estate
│   │   ├── Directory/           # Configurable catalogs
│   │   ├── Notification/        # All notification channels
│   │   ├── Search/              # Elasticsearch integration
│   │   ├── Webhook/             # Outgoing webhooks
│   │   ├── FileStorage/         # MinIO/S3 abstraction
│   │   └── Assistant/           # AI copilot (multi-provider LLM)
│   ├── config/
│   ├── migrations/
│   ├── tests/
│   ├── composer.json
│   └── ...
├── frontend/
│   ├── src/
│   │   ├── modules/
│   │   │   ├── auth/
│   │   │   ├── organization/
│   │   │   ├── tasks/
│   │   │   ├── workflow/
│   │   │   ├── resources/
│   │   │   ├── directories/
│   │   │   ├── notifications/
│   │   │   ├── search/
│   │   │   └── assistant/
│   │   ├── shared/              # UI components, composables, utils
│   │   ├── router/
│   │   ├── stores/
│   │   └── App.vue
│   ├── package.json
│   └── ...
├── docker/
│   ├── php/Dockerfile
│   ├── nginx/
│   ├── node/Dockerfile
│   └── ...
├── proto/                       # gRPC proto definitions
├── docker-compose.yml
├── docker-compose.override.yml
├── Makefile
├── .github/workflows/
└── docs/
    ├── PROJECT_PLAN.md          # This file
    ├── ARCHITECTURE.md
    └── adr/                     # Architecture Decision Records
```

---

### PHASE 9: AI Assistant (Weeks 55-62)

**Goal**: AI copilot що допомагає користувачам створювати та управляти структурами, процесами та задачами через природну мову.

#### Week 55-56: AI Infrastructure — Multi-Provider + Chat Backend
- [ ] Новий модуль `Assistant` (Domain/Application/Infrastructure/Presentation)
- [ ] Domain: Conversation entity (id, userId, organizationId, title, createdAt)
- [ ] Domain: Message entity (id, conversationId, role: user|assistant|system, content, toolCalls, createdAt)
- [ ] Domain: ToolCall entity (id, messageId, toolName, arguments, result, status)
- [ ] Multi-provider AI service з fallback:
  - **Provider priority**: Claude API (Anthropic) → ChatGPT (OpenAI) → Gemini (Google)
  - Спільний interface `AiProviderInterface`:
    ```php
    interface AiProviderInterface {
        function chat(array $messages, array $tools = []): AiResponse;
        function isAvailable(): bool;  // перевірка ліміту/квоти
        function getName(): string;
    }
    ```
  - `AiProviderChain` — пробує провайдери по черзі, якщо один повернув rate limit / error → наступний
  - Конфігурація через .env: `AI_CLAUDE_API_KEY`, `AI_OPENAI_API_KEY`, `AI_GOOGLE_API_KEY`
  - Кожен провайдер — окремий Infrastructure adapter
- [ ] Infrastructure: ClaudeProvider (Anthropic API, tool use / function calling)
- [ ] Infrastructure: OpenAiProvider (OpenAI API, function calling)
- [ ] Infrastructure: GeminiProvider (Google AI API, function calling)
- [ ] Application: SendMessageCommand + Handler (orchestrates AI call + tool execution)
- [ ] REST API:
  - POST /api/v1/assistant/conversations (create)
  - GET  /api/v1/assistant/conversations (list user's conversations)
  - POST /api/v1/assistant/conversations/{id}/messages (send message)
  - GET  /api/v1/assistant/conversations/{id}/messages (history)
  - DELETE /api/v1/assistant/conversations/{id} (delete)
- [ ] Streaming: SSE через Mercure для real-time відповідей AI

#### Week 57-58: Tool Registry — Read-Only Phase (Navigation Help)
- [ ] Tool Registry — маппінг tool definitions → internal handlers:
  ```php
  interface AiToolInterface {
      function getName(): string;
      function getDescription(): string;
      function getParameters(): array;    // JSON Schema
      function execute(array $args, SecurityContext $ctx): ToolResult;
      function isDestructive(): bool;     // requires confirmation
  }
  ```
- [ ] Read-only tools (Phase 1 — безпечні, без confirmation):
  - `list_organizations` — список організацій користувача
  - `get_org_chart` — org chart конкретної організації
  - `list_departments` — департаменти організації
  - `list_employees` — працівники (з фільтрами)
  - `list_positions` — позиції департаменту
  - `get_employee_details` — деталі працівника
  - `search_users` — пошук користувачів
  - `list_tasks` — задачі (Phase 2+)
  - `get_process_definition` — опис процесу (Phase 3+)
  - `navigate_to` — повертає URL для навігації у фронтенді
- [ ] System prompt з контекстом: поточний user, organization, роль, permissions
- [ ] Frontend: Chat sidebar component (collapsible panel)
  - Message bubbles (user + assistant)
  - Streaming text display
  - Navigation links в повідомленнях (clickable → router.push)
  - "Thinking..." indicator

#### Week 59-60: Tool Registry — Write Phase (Create & Edit)
- [ ] Write tools (потребують Preview + Confirm):
  - `create_organization` — створити організацію
  - `create_department` — створити департамент
  - `create_position` — створити позицію
  - `hire_employee` — найняти працівника
  - `invite_user` — запросити користувача
  - `set_manager` — призначити менеджера
  - `create_task` — створити задачу (Phase 2+)
  - `start_process` — запустити процес (Phase 3+)
  - `create_process_definition` — створити workflow (Phase 3+)
- [ ] **Action Preview + Confirm** pattern:
  - AI генерує план дій (список tool calls)
  - Бекенд зберігає pending actions в `ToolCall` зі статусом `pending_confirmation`
  - Фронтенд показує Action Preview card:
    ```
    📋 Plan:
    1. ✅ Create department "Engineering" (code: ENG)
    2. ✅ Create position "CTO" (head) in Engineering
    3. ✅ Create position "Senior Developer" in Engineering
    [Execute All] [Edit] [Cancel]
    ```
  - User confirms → бекенд виконує pending tool calls послідовно
  - Якщо помилка на кроці N → rollback попередніх (або часткове виконання + повідомлення)
- [ ] Frontend: ActionPreview component (checklist з деталями кожної дії)
- [ ] Frontend: Inline action buttons у чаті (Confirm / Edit / Cancel)

#### Week 61-62: Safety Layer + Destructive Operations
- [ ] Destructive tools (додатковий рівень захисту):
  - `delete_department` — видалити департамент
  - `dismiss_employee` — звільнити працівника
  - `delete_position` — видалити позицію
  - `cancel_process` — скасувати процес (Phase 3+)
- [ ] **DependencyChecker** service:
  ```php
  class DependencyChecker {
      /** Перевіряє залежності перед видаленням */
      function check(string $entityType, string $entityId): DependencyReport;
  }
  class DependencyReport {
      public array $blocking;   // не можна видалити (active processes, etc.)
      public array $warnings;   // буде видалено каскадно (positions, employees)
      public bool $safeToDelete;
  }
  ```
  - Department: перевірка employees, positions, sub-departments, active processes
  - Position: перевірка employees на цій позиції
  - Employee: перевірка підлеглих (manager_id references)
- [ ] AI отримує DependencyReport і показує користувачу:
  ```
  ⚠️ Deleting department "Engineering":
  - 3 positions will be removed
  - 5 employees will lose their department
  - 2 active processes reference this department (BLOCKING)

  ❌ Cannot delete: 2 active processes depend on this department.
  Resolve them first, or cancel the processes.
  ```
- [ ] Rate limiting для AI tool calls (prevent abuse)
- [ ] Audit log: всі AI-initiated дії логуються з `initiated_by: ai_assistant`
- [ ] Permission enforcement: AI tools run within user's RBAC permissions
- [ ] Frontend: Destructive action warning cards (red border, explicit confirm)
- [ ] Frontend: Conversation history (user can review what AI did)

---

### PHASE 10: AI-Powered Dynamic Dashboards + Universal Data Import (Weeks 63-70)

**Goal**: Користувачі створюють власні віджети через AI + імпортують дані з CSV/Excel через інтелектуальний маппінг.

#### Week 63-65: Dynamic Widget Builder
- [ ] Domain: WidgetDefinition entity (per user/organization):
  ```
  id, userId, organizationId, title, description
  dataSource: 'tasks' | 'employees' | 'resources' | 'processes' | 'directory_items'
  aggregation: 'count' | 'sum' | 'avg' | 'min' | 'max' | 'list'
  groupBy: string | null (field name)
  filters: jsonb (field → value conditions)
  visualization: 'pie' | 'bar' | 'line' | 'number' | 'table' | 'progress' | 'list'
  config: jsonb (colors, labels, size, refresh interval)
  sortOrder: int (position on dashboard)
  ```
- [ ] Domain: Dashboard entity (named dashboard, collection of widgets)
  - Користувач може мати кілька dashboards
  - Default dashboard створюється автоматично
- [ ] Application: WidgetDataResolver service:
  - Приймає WidgetDefinition → виконує query → повертає structured data
  - Працює через QueryBus (окремі query per dataSource)
  - Кешування результатів (Redis, configurable TTL per widget)
- [ ] AI tool `create_widget`:
  - User: "Покажи кількість задач по статусах для процесу Onboarding"
  - AI аналізує → створює WidgetDefinition → повертає preview
  - User: "Зроби це bar chart замість pie" → AI модифікує visualization
  - User: "Додай фільтр по департаменту Engineering" → AI додає filter
- [ ] AI tool `modify_widget` — змінити існуючий віджет через AI
- [ ] REST API:
  - CRUD /api/v1/dashboards
  - CRUD /api/v1/dashboards/{id}/widgets
  - GET  /api/v1/widgets/{id}/data (resolved data for rendering)
  - POST /api/v1/widgets/{id}/refresh (force cache clear)
- [ ] Frontend: DynamicWidget.vue — universal renderer:
  - Props: widgetDefinition + data
  - Вибирає Chart.js компонент по visualization type
  - Responsive grid layout (CSS Grid або PrimeVue responsive utils)
  - Drag & drop reorder widgets
  - Widget settings (click → edit panel)
  - Auto-refresh (configurable interval via Mercure або polling)
- [ ] Frontend: Dashboard page refactor:
  - Поточний static dashboard → configurable dashboard
  - "Add Widget" button → або AI chat, або manual form
  - Widget templates (pre-built common widgets for quick start)

#### Week 66-68: Universal Data Import (CSV/Excel)
- [ ] Domain: ImportJob entity:
  ```
  id, userId, organizationId, status: 'uploaded' | 'mapping' | 'validating' | 'importing' | 'done' | 'failed'
  fileName, fileSize, filePath (MinIO)
  targetEntity: 'users' | 'employees' | 'departments' | 'positions' | 'resources' | 'directory_items'
  columnMappings: jsonb (csv_column → entity_field)
  totalRows, processedRows, successRows, errorRows
  errors: jsonb (row_number → error messages)
  createdAt, completedAt
  ```
- [ ] Application: UploadImportFileCommand → зберігає файл в MinIO, створює ImportJob
- [ ] Application: AnalyzeImportCommand → парсить header + перші N рядків
  - Повертає: column names, sample data, detected types
- [ ] AI tool `analyze_import`:
  - Отримує header + sample rows + target entity schema
  - Пропонує column mapping (csv column → entity field)
  - Визначає: які колонки matched, які unmapped, які мають warnings
  - Користувач може скоригувати маппінг
- [ ] Application: ValidateImportCommand → dry-run validation:
  - Перевіряє всі рядки за маппінгом
  - Повертає: valid count, error count, errors per row
  - НЕ створює entities — тільки валідація
- [ ] Application: ExecuteImportCommand → async через RabbitMQ:
  - Обробляє рядки batch по 100
  - Для кожного рядка → відповідний CreateCommand (hire, create resource, etc.)
  - Progress tracking через Redis (processedRows counter)
  - Progress updates через Mercure (real-time progress bar)
  - Помилки логуються в ImportJob.errors
- [ ] Supported import targets:
  - **Users**: email, firstName, lastName → RegisterUserCommand
  - **Employees**: user email + department + position + empNumber → HireEmployeeCommand
  - **Departments**: name, code, parentCode → CreateDepartmentCommand
  - **Positions**: name, departmentCode, isHead → CreatePositionCommand
  - **Resources**: name, type, attributes → CreateResourceCommand (Phase 4+)
  - **Directory Items**: dynamic fields → CreateDirectoryItemCommand (Phase 4+)
- [ ] Excel support: PhpSpreadsheet library для .xlsx/.xls парсинг
- [ ] REST API:
  - POST /api/v1/imports/upload (multipart file upload)
  - GET  /api/v1/imports/{id} (status + progress)
  - POST /api/v1/imports/{id}/mapping (set/update column mappings)
  - POST /api/v1/imports/{id}/validate (dry-run)
  - POST /api/v1/imports/{id}/execute (start import)
  - GET  /api/v1/imports/{id}/errors (error report)
  - GET  /api/v1/imports (list user's imports)
- [ ] Frontend: ImportWizard component (multi-step):
  1. Upload file (drag & drop zone)
  2. Preview data (table з перших 10 рядків)
  3. Column mapping (AI-suggested + manual override, drag lines between columns)
  4. Validation report (green/yellow/red rows)
  5. Import progress (real-time progress bar via Mercure)
  6. Result summary (success count, error download)
- [ ] Frontend: AI-assisted import через chat:
  - User: "Завантаж цей файл як обладнання" + drag file into chat
  - AI: аналізує, пропонує маппінг, запитує підтвердження
  - User: "Так, імпортуй" → прогрес в чаті

#### Week 69-70: Export + Templates
- [ ] Export: генерація CSV/Excel з будь-якої таблиці
  - GET /api/v1/exports?entity=employees&format=csv&filters=...
  - Async для великих наборів (через RabbitMQ, download link через Mercure)
- [ ] Import templates: pre-built CSV templates для кожної сутності
  - GET /api/v1/imports/templates/{entity} → завантажити шаблон CSV з headers
  - Шаблон включає приклад рядка + коментарі в header
- [ ] AI tool `export_data` — "Експортуй список всіх працівників Engineering в Excel"
- [ ] Frontend: Export button на кожній list-сторінці (employees, resources, etc.)
- [ ] Frontend: Template download на import wizard (Step 1)

#### Week 71-74: Report Builder (Saved Reports + Sharing)
- [ ] Domain: Report entity (повноцінний збережений звіт):
  ```
  id, organizationId, createdBy (userId), title, description, slug
  sections: jsonb — масив секцій звіту, кожна секція:
    - type: 'chart' | 'table' | 'number' | 'text' | 'editable_table'
    - dataSource: 'tasks' | 'employees' | 'resources' | 'process_instances' | 'directory_items'
    - processDefinitionId: string | null (для звітів по конкретному процесу)
    - fields: string[] (які поля показувати, включаючи dynamic process fields)
    - aggregation: 'count' | 'sum' | 'avg' | 'min' | 'max' | null
    - groupBy: string | null
    - orderBy: string | null
    - staticFilters: jsonb (вбудовані в звіт, юзер не змінює)
    - interactiveFilters: jsonb (юзер може змінювати при перегляді)
      - { field: "department_id", type: "select", label: "Department" }
      - { field: "date_range", type: "daterange", label: "Period" }
    - visualization: 'bar' | 'pie' | 'line' | 'grouped_bar' | 'stacked_bar' | 'table' | 'number_card' | 'timeline' | 'calendar'
    - config: jsonb (colors, labels, axis config, size)
  status: 'draft' | 'published'
  refreshInterval: int | null (seconds, null = manual refresh)
  createdAt, updatedAt
  ```
- [ ] Domain: ReportAccess entity (sharing model):
  ```
  id, reportId
  accessType: 'user' | 'role' | 'department' | 'organization'
  accessTargetId: string (userId | roleId | departmentId | null for org-wide)
  permission: 'view' | 'edit'
  ```
- [ ] Domain: ReportSnapshot entity (optional — scheduled snapshots):
  ```
  id, reportId, data: jsonb, generatedAt
  ```
  - Для звітів що потрібно порівнювати з попередніми періодами
  - Cron job: щоденно/щотижнево зберігає snapshot
- [ ] Application: ReportDataResolver service:
  - Приймає Report section config + interactive filter values → виконує query
  - Підтримує dynamic process fields (JSONB query в PostgreSQL)
  - Підтримує cross-entity joins (tasks + employees, resources + departments)
  - Кешування в Redis (TTL per report)
- [ ] Application: CRUD commands/queries для Report
- [ ] AI tools:
  - `create_report` — "Створи звіт планових vs фактичних робіт по процесу Maintenance"
  - `modify_report` — "Додай фільтр по об'єкту і зміни на grouped bar chart"
  - `share_report` — "Розшар цей звіт для всього відділу Engineering"
- [ ] REST API:
  - CRUD /api/v1/organizations/{id}/reports
  - GET  /api/v1/organizations/{id}/reports/{id}/data?filters[dept]=...  (live data)
  - PUT  /api/v1/organizations/{id}/reports/{id}/access (manage sharing)
  - GET  /api/v1/organizations/{id}/reports/{id}/snapshots (historical)
  - POST /api/v1/organizations/{id}/reports/{id}/snapshot (force snapshot)
- [ ] Frontend: ReportPage.vue — full-page report viewer:
  - Renders multiple sections (chart + table + number cards on one page)
  - Interactive filter bar at top (dropdowns, date range pickers)
  - Auto-refresh indicator
  - Share button → modal з access management
  - Export report data (CSV/Excel/PDF)
  - "Edit" mode для report creator (add/remove/reorder sections)
- [ ] Frontend: ReportBuilder.vue — visual report editor:
  - Section list (add, remove, reorder via drag & drop)
  - Per-section config: data source, fields, visualization, filters
  - Live preview при зміні конфігурації
  - "Ask AI" button → describe what you want in natural language
- [ ] Frontend: ReportList page (gallery of org's reports):
  - Cards з preview thumbnail, title, description, author
  - "My Reports" / "Shared with me" / "All" tabs
  - Quick create: from template або through AI
- [ ] Editable table section type (для ручного вводу даних):
  - Rows: entities (employees, resources, etc.)
  - Columns: dynamic fields (configurable per report)
  - Cells: inline editable (saves to custom data store)
  - Use case: табель робочого часу, журнал обходів, чеклісти
  - Зберігається як Directory Items або custom JSONB entries
- [ ] Widget ↔ Report connection:
  - Widget на dashboard = "pinned section" з Report
  - Click widget → opens full Report page
  - "Pin to dashboard" button on any report section

---

## Interview Talking Points (by Phase)

| Phase | What you can demonstrate |
|---|---|
| 0 | Docker, CI/CD, Clean Architecture, DDD structure |
| 1 | JWT auth, RBAC, tree structures, multi-tenancy, Symfony Security |
| 2 | CQRS, async messaging (RabbitMQ), file storage (S3), state machines |
| 3 | Event Sourcing, BPMN, expression evaluation, complex domain modeling |
| 4 | EAV/JSONB patterns, polymorphic entities, dynamic schemas |
| 5 | Elasticsearch, real-time (Mercure/SSE), webhook delivery, event-driven |
| 6 | GraphQL, gRPC, API design, protocol comparison |
| 7 | Microservices extraction, service discovery, distributed systems |
| 8 | Full-stack, analytics, OAuth2, production readiness |
| 9 | AI integration, multi-provider fallback, tool use, safety patterns, LLM orchestration |
| 10 | Dynamic dashboards, data import/export, report builder, AI-driven configuration, no-code BI |
