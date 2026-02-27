# Codebase Structure

**Analysis Date:** 2026-02-27

## Directory Layout

```
procivo/
├── backend/                        # Symfony PHP application
│   ├── src/
│   │   ├── Shared/                 # Core abstractions + buses
│   │   ├── Identity/               # User auth module
│   │   ├── Organization/           # Org structure + RBAC
│   │   ├── TaskManager/            # Tasks + Kanban
│   │   ├── Workflow/               # BPMN engine + processes
│   │   ├── Notification/           # Notifications module
│   │   ├── Resource/               # Generic resources
│   │   ├── Directory/              # Directory services
│   │   ├── Search/                 # Elasticsearch integration
│   │   └── Kernel.php              # Symfony microkernel
│   ├── config/
│   │   ├── services.yaml           # DI container configuration
│   │   ├── routes.yaml             # Route entry point (→ routing.controllers)
│   │   ├── packages/
│   │   │   └── messenger.yaml      # Messenger bus config (command/query/event)
│   │   ├── routes/
│   │   │   ├── security.yaml       # Security routes
│   │   │   └── framework.yaml      # Framework routes
│   │   └── jwt/                    # JWT key files
│   ├── migrations/                 # Doctrine migrations (auto-generated)
│   ├── tests/
│   │   └── Unit/                   # PHPUnit tests
│   ├── public/                     # Web root (index.php)
│   ├── bin/
│   │   └── console                 # Symfony CLI
│   ├── templates/                  # Twig email templates
│   ├── translations/
│   │   ├── messages.en.json        # English translations
│   │   └── messages.uk.json        # Ukrainian translations
│   └── composer.json               # PHP dependencies
│
├── frontend/                       # Vue 3 + TypeScript application
│   ├── src/
│   │   ├── modules/
│   │   │   ├── auth/               # Login/register
│   │   │   ├── organization/       # Org details, employees, departments
│   │   │   ├── tasks/              # Task list, detail, Kanban
│   │   │   ├── workflow/           # Process designer, instances
│   │   │   └── notifications/      # Notification UI
│   │   ├── shared/
│   │   │   ├── api/
│   │   │   │   └── http-client.ts  # Axios with auth interceptor
│   │   │   ├── components/         # Reusable UI components
│   │   │   ├── composables/        # Vue composables
│   │   │   ├── layouts/            # Page layouts (DashboardLayout)
│   │   │   ├── types/              # Global TypeScript types
│   │   │   └── utils/              # Utilities (error handling, formatting)
│   │   ├── router/
│   │   │   └── index.ts            # Vue Router configuration
│   │   ├── i18n/
│   │   │   └── locales/            # Language JSON files
│   │   ├── assets/                 # Static images, styles
│   │   ├── App.vue                 # Root component
│   │   └── main.ts                 # Entry point
│   ├── public/                     # Static public files
│   ├── package.json                # Node.js dependencies
│   ├── vite.config.ts              # Vite configuration
│   └── tsconfig.json               # TypeScript configuration
│
├── docker/                         # Docker configurations
│   └── [docker compose services]
│
├── docs/                           # Documentation
│   ├── PROJECT_PLAN.md             # Overall project roadmap
│   └── WORKFLOW_TASKS_INTEGRATION_PLAN.md
│
├── docker-compose.yml              # Main docker-compose file
├── Makefile                        # Development commands
└── .planning/
    └── codebase/                   # GSD codebase documentation
```

## Directory Purposes

### Backend Structure (`backend/src/`)

**Shared Module:**
- **Purpose:** Shared kernel, base classes, cross-cutting services
- **Contains:**
  - `Application/Bus/`: CommandBusInterface, QueryBusInterface, EventBusInterface
  - `Application/Command/`, `Query/`: Base marker interfaces
  - `Domain/`: AggregateRoot, DomainEvent, Repository (abstract)
  - `Domain/ValueObject/`: Common value objects (ids, enums)
  - `Infrastructure/Bus/`: Messenger implementations + Middleware
  - `Infrastructure/Persistence/`: Doctrine Type converters
- **Key files:**
  - `src/Shared/Application/Bus/CommandBusInterface.php`
  - `src/Shared/Infrastructure/Bus/MessengerCommandBus.php`
  - `src/Shared/Infrastructure/Bus/Middleware/DispatchDomainEventsMiddleware.php`

**Identity Module:**
- **Purpose:** User authentication and authorization
- **Structure:**
  - `Domain/Entity/User.php`: AggregateRoot (email, password, status)
  - `Domain/ValueObject/`: UserStatus, UserEmail (enums/classes)
  - `Application/Command/`: RegisterUser, Login, RefreshToken, ChangePassword
  - `Application/Query/`: GetCurrentUser, SearchUsers
  - `Infrastructure/Security/`: JwtCreatedListener, LexikJwtTokenManager, RefreshTokenService
  - `Infrastructure/Hashing/`: SymfonyPasswordHasher
  - `Presentation/Controller/UserController.php`: `/api/v1/auth/*` routes
- **Key files:**
  - `src/Identity/Domain/Entity/User.php`
  - `src/Identity/Infrastructure/Security/RefreshTokenService.php` (Redis)
  - `src/Identity/Presentation/Controller/UserController.php`

**Organization Module:**
- **Purpose:** Organizational hierarchy, employees, roles, permissions
- **Structure:**
  - `Domain/Entity/`: Organization, Department, Employee, Position, EmployeeRole
  - `Domain/ValueObject/`: EmployeeStatus, RolePermission
  - `Application/Command/`: Create/Update/Delete for all entities, HireEmployee, DismissEmployee
  - `Application/Query/`: GetOrgChart, GetDepartmentTree, ListEmployees, ListRoles
  - `Infrastructure/Repository/`: Doctrine implementations
  - `Infrastructure/Service/`: SymfonyInvitationMailer, SecurityCurrentUserProvider
  - `Infrastructure/Security/`: OrganizationAuthorizer (permission checker)
  - `Presentation/Controller/`: Organization, Department, Employee, Role controllers
- **Key files:**
  - `src/Organization/Domain/Entity/Organization.php`
  - `src/Organization/Infrastructure/Service/SymfonyInvitationMailer.php`
  - `src/Organization/Infrastructure/Security/OrganizationAuthorizer.php`
  - `src/Organization/Presentation/Security/PermissionVoter.php` (Symfony security voter)

**TaskManager Module:**
- **Purpose:** Task tracking, Kanban boards, assignments, attachments
- **Structure:**
  - `Domain/Entity/`: Task, Board, BoardColumn, Label, Comment, TaskAssignment, TaskAttachment
  - `Domain/ValueObject/`: TaskStatus, TaskPriority, TaskId, AssignmentStrategy
  - `Domain/Event/`: TaskCreatedEvent, TaskAssignedEvent, TaskStatusChangedEvent, etc.
  - `Application/Command/`: CreateTask, UpdateTask, TransitionTask, ClaimTask, UnclaimTask, AddComment, etc.
  - `Application/Query/`: GetTask, ListTasks, GetBoard, ListBoards, ListComments
  - `Application/Service/`: AssignmentResolver (logic for resolving assignees)
  - `Application/DTO/`: TaskDTO, BoardDTO
  - `Application/Port/`: FileStorageInterface, OrganizationQueryPort (external adapters)
  - `Infrastructure/Repository/`: Doctrine implementations
  - `Infrastructure/Storage/`: S3FileStorage (attachment upload)
  - `Infrastructure/Organization/`: DoctrineOrganizationQueryAdapter
  - `Presentation/Controller/`: TaskController, BoardController, LabelController
- **Key files:**
  - `src/TaskManager/Domain/Entity/Task.php`
  - `src/TaskManager/Application/Service/AssignmentResolver.php`
  - `src/TaskManager/Infrastructure/Storage/S3FileStorage.php`
  - `src/TaskManager/Presentation/Controller/TaskController.php`
  - `src/TaskManager/Domain/Repository/TaskRepositoryInterface.php`

**Workflow Module (BPMN Engine):**
- **Purpose:** Process definition design, process instance execution, token flow
- **Structure:**
  - `Domain/Entity/`: ProcessDefinition, Node, Transition, ProcessInstance, WorkflowTaskLink
  - `Domain/ValueObject/`: ProcessInstanceStatus, NodeType, ProcessDefinitionId, TokenId
  - `Domain/Event/`: TaskNodeActivatedEvent, ProcessStartedEvent, TokenMovedEvent, WebhookFiredEvent, TimerScheduledEvent, etc.
  - `Domain/Service/`: ExpressionEvaluator (condition evaluation), WorkflowEngine (token execution)
  - `Domain/Repository/`: Process*, Node*, Transition*, ProcessInstance*, WorkflowTaskLink* interfaces
  - `Application/Command/`: CreateProcessDefinition, UpdateNode, StartProcess, ExecuteTaskAction, CancelProcess
  - `Application/Query/`: GetProcessDefinition, ListProcessDefinitions, GetProcessInstance, GetProcessInstanceGraph
  - `Application/EventHandler/`: OnTaskNodeActivated (dispatches CreateTask), OnWebhookNodeActivated, OnNotificationNodeActivated, etc.
  - `Infrastructure/Repository/`: Doctrine and EventSourced implementations
  - `Infrastructure/EventStore/`: DoctrineEventStore (event sourcing for ProcessInstance reconstruction)
  - `Infrastructure/Timer/`: RabbitMqTimerService (schedule timers, process later)
  - `Infrastructure/Webhook/`: ExecuteWebhookMessage, webhook execution
  - `Presentation/Controller/`: ProcessDefinitionController, ProcessInstanceController
- **Key files:**
  - `src/Workflow/Domain/Entity/ProcessInstance.php` (event sourced)
  - `src/Workflow/Domain/Service/WorkflowEngine.php` (token execution)
  - `src/Workflow/Application/EventHandler/OnTaskNodeActivated.php` (workflow → task bridge)
  - `src/Workflow/Infrastructure/EventStore/DoctrineEventStore.php`
  - `src/Workflow/Domain/Repository/WorkflowTaskLinkRepositoryInterface.php`

**Notification Module:**
- **Purpose:** Notification creation and management
- **Structure:**
  - `Domain/Entity/`: Notification
  - `Domain/ValueObject/`: NotificationType
  - `Application/Command/`: MarkAsRead, MarkAllAsRead
  - `Application/Query/`: ListNotifications, CountUnread
  - `Application/EventHandler/`: Listeners for task events
  - `Infrastructure/Repository/`: DoctrineNotificationRepository
  - `Presentation/Controller/`: NotificationController
- **Key files:**
  - `src/Notification/Domain/Entity/Notification.php`
  - `src/Notification/Application/EventHandler/OnTaskAssigned.php` (creates notification)

**Backend Configuration:**
- `config/services.yaml`: DI binding (interfaces → implementations), external services
- `config/packages/messenger.yaml`: Bus definitions, transport routing, retry strategy
- `config/routes/`: Security routes, framework configuration
- `config/jwt/`: JWT RSA key pairs (private.pem, public.pem)

### Frontend Structure (`frontend/src/`)

**Router & Layouts:**
- **Location:** `frontend/src/router/index.ts`
- **Pattern:** Nested routes, auth guards, org-scoped resource routes
- **Root Layout:** `frontend/src/shared/layouts/DashboardLayout.vue` (sidebar, top bar)

**Module: Auth**
- **Location:** `frontend/src/modules/auth/`
- **Contains:**
  - `pages/`: LoginPage.vue, RegisterPage.vue
  - `stores/auth.store.ts`: User state, token management
  - `api/user.api.ts`: Auth API calls
  - `types/auth.types.ts`: TypeScript interfaces
- **Pattern:** Pinia store with persistent tokens (localStorage)

**Module: Organization**
- **Location:** `frontend/src/modules/organization/`
- **Contains:**
  - `pages/`: OrganizationsPage, OrganizationDetailPage, DepartmentsPage, EmployeesPage, OrgChartPage, RolesPage
  - `stores/organization.store.ts`: Organization state
  - `api/organization.api.ts`: API calls
  - `types/organization.types.ts`: Interfaces
- **Pattern:** Master-detail for departments/employees within org context

**Module: Tasks**
- **Location:** `frontend/src/modules/tasks/`
- **Contains:**
  - `pages/`:
    - TasksPage.vue: Master list + detail panel (responsive)
    - TaskDetailPanel.vue: Inline detail view (desktop)
    - TaskDetailFullPage.vue: Full-page detail (mobile)
    - BoardsPage.vue: Kanban board list
    - KanbanBoardPage.vue: Kanban board columns
    - LabelsPage.vue: Label management
  - `components/`:
    - TaskListPanel.vue: Task list with search/filters
    - TaskDetailContent.vue: Task information display
    - TaskCreateDialog.vue: New task form
    - TaskFormDialog.vue: Edit task form
    - ActionFormDialog.vue: Dynamic form for workflow actions
    - TaskAssignments.vue: Assignment controls
    - TaskAttachments.vue: File uploads
    - TaskComments.vue: Comment thread
    - StatusDropdownButton.vue: Status transition selector
    - ProcessHistoryTimeline.vue: Workflow history
  - `stores/task.store.ts`: Task list, current task, actions
  - `api/task.api.ts`: CRUD operations
  - `types/task.types.ts`: TaskDTO, TaskDetailDTO
- **Pattern:** Master-detail view with reactive panel updates

**Module: Workflow**
- **Location:** `frontend/src/modules/workflow/`
- **Contains:**
  - `pages/`:
    - ProcessDefinitionsPage.vue: List process definitions
    - ProcessDesignerPage.vue: Workflow canvas (vue-flow)
    - ProcessInstancesPage.vue: Running processes
    - ProcessInstanceDetailPage.vue: Process execution history/state
  - `components/`:
    - WorkflowDesigner.vue: Main designer canvas
    - nodes/: StartNode.vue, EndNode.vue, TaskNode.vue, GatewayNode.vue, TimerNode.vue
    - NodePropertyPanel.vue: Edit node config (title, assignment, etc.)
    - TaskNodeConfig.vue: Task-specific config
    - WebhookNodeConfig.vue: Webhook config
    - SubProcessNodeConfig.vue: Sub-process config
    - FormFieldsBuilder.vue: Dynamic form generation
    - ProcessMonitorGraph.vue: Real-time process visualization
  - `stores/process-instance.store.ts`: Process state
  - `api/`:
    - process-definition.api.ts: CRUD definitions, nodes, transitions
    - process-instance.api.ts: Start, cancel, query processes
  - `types/process-*.types.ts`: DTOs
  - `composables/useCanvasValidation.ts`: Validation logic (orphan nodes, cycles)
  - `data/process-templates.ts`: Preset templates
- **Pattern:** Canvas-based designer with node/edge selection and property panels

**Module: Notifications**
- **Location:** `frontend/src/modules/notifications/`
- **Contains:**
  - `pages/`: NotificationsPage.vue
  - `stores/notification.store.ts`: Notification list
  - `api/notification.api.ts`: API calls

**Shared Layer:**
- **API Client:** `frontend/src/shared/api/http-client.ts` (Axios with token refresh, error handling)
- **Components:** `frontend/src/shared/components/` (reusable UI components)
- **Composables:** `frontend/src/shared/composables/` (Vue 3 composition API hooks)
- **Layouts:** `frontend/src/shared/layouts/DashboardLayout.vue`
- **Types:** `frontend/src/shared/types/api.types.ts` (common API response types)
- **Utils:**
  - `api-error.ts`: Extract error messages from API responses
  - `status-severity.ts`: Map task status → PrimeVue severity
  - `date-format.ts`: Date formatting utilities
- **i18n:** `frontend/src/i18n/locales/` (en.json, uk.json)

### Docker & Configuration

**Docker:**
- `docker/`: Service Dockerfiles (backend, frontend, etc.)
- `docker-compose.yml`: All services (Nginx, PHP-FPM, PostgreSQL, Redis, RabbitMQ, Mercure, LocalStack)

**Docs:**
- `docs/PROJECT_PLAN.md`: Roadmap, phases, technical decisions
- `docs/WORKFLOW_TASKS_INTEGRATION_PLAN.md`: Current phase details

**Development:**
- `Makefile`: Common commands (docker up, migrate, seed, test)
- Root `.env`: Environment configuration (not committed, see `.env.example`)

## Key File Locations

**Entry Points:**

Backend:
- `backend/src/Kernel.php`: Symfony microkernel
- `backend/public/index.php`: HTTP entry point (bootstraps Kernel)
- `backend/bin/console`: CLI entry point
- `backend/config/routes.yaml`: Route configuration (loads routing.controllers)

Frontend:
- `frontend/src/main.ts`: Application bootstrap (creates app, mounts router, pinia)
- `frontend/src/App.vue`: Root component
- `frontend/src/router/index.ts`: Route definitions
- `frontend/index.html`: HTML entry point (Vite)

**Configuration:**

Backend:
- `backend/config/services.yaml`: DI container (all service bindings)
- `backend/config/packages/messenger.yaml`: Bus and transport config
- `backend/config/packages/doctrine.yaml`: ORM configuration
- `backend/.env`: Environment variables (not committed)
- `backend/composer.json`: PHP dependencies and scripts

Frontend:
- `frontend/vite.config.ts`: Build configuration (alias resolution)
- `frontend/tsconfig.json`: TypeScript configuration
- `frontend/package.json`: Node dependencies
- `frontend/.env.local`: Environment variables (not committed)

**Core Logic:**

Backend:
- `backend/src/Shared/Domain/AggregateRoot.php`: Base for domain entities
- `backend/src/Shared/Infrastructure/Bus/Middleware/DispatchDomainEventsMiddleware.php`: Event dispatch
- `backend/src/Identity/Domain/Entity/User.php`: User model
- `backend/src/Organization/Infrastructure/Security/OrganizationAuthorizer.php`: Permission checks
- `backend/src/TaskManager/Domain/Entity/Task.php`: Task model with state machine
- `backend/src/Workflow/Domain/Entity/ProcessInstance.php`: Event-sourced process
- `backend/src/Workflow/Domain/Service/WorkflowEngine.php`: Token execution logic
- `backend/src/Workflow/Application/EventHandler/OnTaskNodeActivated.php`: Workflow → Task bridge

Frontend:
- `frontend/src/shared/api/http-client.ts`: API communication layer
- `frontend/src/modules/tasks/stores/task.store.ts`: Task state management
- `frontend/src/modules/workflow/components/WorkflowDesigner.vue`: Canvas editor

**Testing:**

Backend:
- `backend/tests/Unit/`: PHPUnit tests
- `backend/phpunit.xml`: PHPUnit configuration
- `backend/phpstan.neon`: Static analysis configuration

Frontend:
- `frontend/vitest.config.ts`: Unit test config (if used)
- `frontend/playwright.config.ts`: E2E test config (if used)

## Naming Conventions

**Files:**

- Entities: `{EntityName}.php` (PascalCase) in `Domain/Entity/`
- Value Objects: `{ValueName}.php` in `Domain/ValueObject/`
  - Enums: `enum TaskStatus: string { case Draft = 'draft'; ... }`
- Events: `{EventName}Event.php` in `Domain/Event/`
- Commands: `{ActionName}Command.php` in `Application/Command/{ActionName}/`
- Queries: `{QueryName}Query.php` in `Application/Query/{QueryName}/`
- Handlers: `{CommandOrQuery}Handler.php` in same directory as command/query
- Repositories: `Doctrine{Entity}Repository.php` in `Infrastructure/Repository/`
- Controllers: `{Resource}Controller.php` in `Presentation/Controller/`
- DTOs: `{Entity}DTO.php` in `Application/DTO/`

**Frontend:**
- Vue components: `PascalCase.vue` (e.g., TaskListPanel.vue, TaskDetailContent.vue)
- Store files: `{domain}.store.ts` (e.g., task.store.ts, process-instance.store.ts)
- API modules: `{resource}.api.ts` (e.g., task.api.ts, process-definition.api.ts)
- Type files: `{resource}.types.ts` (e.g., task.types.ts, process-definition.types.ts)
- Composables: `use{ComposableName}.ts` (e.g., useCanvasValidation.ts)
- Pages: `{Feature}Page.vue` (e.g., TasksPage.vue, ProcessDesignerPage.vue)
- Utility files: `{functionality}.ts` (e.g., api-error.ts, date-format.ts)

**Classes & Namespaces:**

- Classes: PascalCase
- Namespaces: `App\{Module}\{Layer}\{Subdomain}`
  - Example: `App\TaskManager\Application\Command\CreateTask`
- Interfaces: `{Name}Interface` suffix
  - Example: `TaskRepositoryInterface`, `FileStorageInterface`
- Traits: `{Name}Trait` suffix (not used extensively in this project)
- Enums: `{Name}` (treated as value objects)

**Database:**

- Tables: snake_case, plural (e.g., tasks, board_columns, employee_roles)
- Columns: snake_case (e.g., created_at, task_id, due_date)
- Constraints: `fk_{parent_table}_{child_table}` for foreign keys
- Mapping files: `backend/src/{Module}/Infrastructure/Persistence/Doctrine/Mapping/{Entity}.orm.xml`

**API Routes:**

- Base: `/api/v1/`
- Organization-scoped: `/api/v1/organizations/{organizationId}/{resource}`
- Examples:
  - `POST /api/v1/organizations/{orgId}/tasks` (create)
  - `GET /api/v1/organizations/{orgId}/tasks` (list)
  - `GET /api/v1/organizations/{orgId}/tasks/{taskId}` (read)
  - `PUT /api/v1/organizations/{orgId}/tasks/{taskId}` (update)
  - `DELETE /api/v1/organizations/{orgId}/tasks/{taskId}` (delete)
  - `POST /api/v1/organizations/{orgId}/tasks/{taskId}/transition` (action)

## Where to Add New Code

**New Feature (e.g., New Task Type):**
1. Domain entity/value object: `backend/src/TaskManager/Domain/Entity/CustomTask.php`
2. Domain events: `backend/src/TaskManager/Domain/Event/CustomTaskCreatedEvent.php`
3. Command/Handler: `backend/src/TaskManager/Application/Command/CreateCustomTask/CreateCustomTaskCommand.php` + Handler
4. Query/Handler: `backend/src/TaskManager/Application/Query/GetCustomTask/GetCustomTaskQuery.php` + Handler
5. Repository interface: `backend/src/TaskManager/Domain/Repository/CustomTaskRepositoryInterface.php`
6. Doctrine repository: `backend/src/TaskManager/Infrastructure/Repository/DoctrineCustomTaskRepository.php`
7. Doctrine mapping: `backend/src/TaskManager/Infrastructure/Persistence/Doctrine/Mapping/CustomTask.orm.xml`
8. Controller endpoint: `backend/src/TaskManager/Presentation/Controller/TaskController.php` (add method)
9. DTO: `backend/src/TaskManager/Application/DTO/CustomTaskDTO.php`
10. Frontend API: `frontend/src/modules/tasks/api/task.api.ts` (add method)
11. Frontend store: `frontend/src/modules/tasks/stores/task.store.ts` (add actions)
12. Frontend components: `frontend/src/modules/tasks/components/CustomTaskPanel.vue`, etc.
13. Tests: `backend/tests/Unit/{Module}/{Layer}/{Feature}Test.php`

**New Module (e.g., Project Management):**
1. Create directory: `backend/src/ProjectManager/`
2. Add layer subdirectories: `Domain/`, `Application/`, `Infrastructure/`, `Presentation/`
3. Add to `services.yaml`: Resource configuration
4. Create core entities: `Domain/Entity/Project.php`, `Domain/Entity/Task.php`
5. Create repository interfaces: `Domain/Repository/*RepositoryInterface.php`
6. Implement repositories: `Infrastructure/Repository/Doctrine*Repository.php`
7. Create mappings: `Infrastructure/Persistence/Doctrine/Mapping/*.orm.xml`
8. Create commands/queries: `Application/Command/*/`, `Application/Query/*/`
9. Create controller: `Presentation/Controller/ProjectController.php`
10. Create DTOs: `Application/DTO/*.php`
11. Frontend: Create module `frontend/src/modules/project/` with same structure as tasks/workflow

**New Workflow Node Type (e.g., EmailNode):**
1. Domain support: Ensure `ProcessInstance` and `WorkflowEngine` support node type
2. Node entity/mapping: If stored as variant of Node, extend configuration
3. Event handler: `backend/src/Workflow/Application/EventHandler/OnEmailNodeActivated.php`
4. Infrastructure: `backend/src/Workflow/Infrastructure/Email/EmailService.php`
5. Frontend component: `frontend/src/modules/workflow/components/nodes/EmailNode.vue`
6. Node config: `frontend/src/modules/workflow/components/EmailNodeConfig.vue`
7. Tempalate update: `frontend/src/modules/workflow/data/process-templates.ts` (add example)

**New API Port (e.g., SMS Service):**
1. Interface: `backend/src/TaskManager/Application/Port/SmsServiceInterface.php`
2. Implementation: `backend/src/TaskManager/Infrastructure/Service/TwilioSmsService.php`
3. Configuration: Add to `services.yaml` with env variables
4. Usage: Inject into handlers, dispatch from event handlers

## Special Directories

**Migrations:**
- Location: `backend/migrations/`
- Purpose: Doctrine database migrations
- Generated: Yes (auto-generated by `php bin/console doctrine:migrations:generate`)
- Committed: Yes (part of version control)

**Tests:**
- Location: `backend/tests/Unit/`
- Purpose: Unit tests for domain/application logic
- Committed: Yes
- Pattern: Test each module in isolation, mock external dependencies

**Config JWT:**
- Location: `backend/config/jwt/`
- Purpose: RSA key pairs for JWT signing
- Generated: Yes (generated during setup)
- Committed: No (gitignored for security)

**Cache:**
- Location: `backend/var/cache/`
- Purpose: Symfony cache (compiled config, container)
- Generated: Yes (auto-generated by framework)
- Committed: No (gitignored)

**Logs:**
- Location: `backend/var/log/`
- Purpose: Application logs
- Generated: Yes (written at runtime)
- Committed: No (gitignored)

**Vendor:**
- Location: `backend/vendor/`, `frontend/node_modules/`
- Purpose: Dependencies
- Generated: Yes (installed by composer/npm)
- Committed: No (gitignored, managed via lockfile)

**Translations:**
- Location: `backend/translations/`, `frontend/src/i18n/locales/`
- Purpose: i18n message files
- Format: JSON key-value pairs
- Committed: Yes
- Pattern: English + Ukrainian

---

*Structure analysis: 2026-02-27*
