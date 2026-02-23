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

#### Week 3: CI/CD + Dev Tools
- [ ] GitHub repository init
- [ ] GitHub Actions pipeline:
  - PHP lint (php-cs-fixer)
  - PHPStan level 6 (піднімемо до 8 поступово)
  - PHPUnit tests
  - Docker build test
- [ ] Налаштувати PHPStan + baseline
- [ ] Налаштувати PHP CS Fixer
- [ ] Pre-commit hooks (GrumPHP або Captainhook)
- [ ] Створити .env.example та документацію по запуску

---

### PHASE 1: Identity + Organization Structure (Weeks 4-9)

**Goal**: Auth system, users, org hierarchy, RBAC — повноцінний identity module.

#### Week 4-5: Identity Module (Auth) ✅
- [x] Domain: User entity (id, email, password, status: pending/active/blocked)
- [ ] Domain: Role, Permission entities (deferred to Week 6-7 with RBAC)
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

#### Week 6-7: Organization Structure Module
- [ ] Domain: Organization entity (tenant)
- [ ] Domain: OrganizationalUnit entity (tree structure, parent_id)
- [ ] Domain: Position entity (position within org unit)
- [ ] Domain: UserPosition (M2M linking user to position(s))
- [ ] Domain ValueObjects: OrganizationId, UnitType (enum), PositionTitle
- [ ] Application: CreateOrganization, InviteUser, CreateUnit, MoveUnit
- [ ] Application: BuildOrgTreeQuery — повертає дерево для фронту
- [ ] Infrastructure: Doctrine nested set або materialized path для дерева
- [ ] REST API endpoints:
  - CRUD /api/v1/organizations
  - CRUD /api/v1/organizations/{id}/units (tree operations)
  - CRUD /api/v1/organizations/{id}/positions
  - POST /api/v1/organizations/{id}/units/{unitId}/move
  - GET  /api/v1/organizations/{id}/tree (full hierarchy)
- [ ] Multi-tenancy: tenant filter on all queries (Doctrine filter)
- [ ] RBAC: Symfony Voters для перевірки прав
- [ ] Seeder: demo organization з тестовою ієрархією
- [ ] Tests

#### Week 8-9: Frontend — Auth + Org Structure
- [ ] Vue 3 + TypeScript + Vite init
- [ ] Pinia stores: auth, organization
- [ ] Vue Router + auth guards (redirect to login)
- [ ] PrimeVue setup + theme customization
- [ ] Login / Register pages
- [ ] Dashboard layout (sidebar, topbar, content area)
- [ ] Org chart component (інтерактивна схема):
  - Tree visualization (vue-org-chart або custom з D3.js)
  - Click node → sidebar з деталями
  - Add/edit/delete units (modal forms)
  - Drag & drop для переміщення units
- [ ] User management page (table + CRUD)
- [ ] Responsive design basics

---

### PHASE 2: Task Manager (Weeks 10-15)

**Goal**: Повноцінний task management — boards, tasks, comments, файли.

#### Week 10-11: Task Domain
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

#### Week 12: Task API + File uploads
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

#### Week 13: RabbitMQ Integration
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

#### Week 14-15: Frontend — Task Manager
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

### PHASE 3: Workflow Engine — Core (Weeks 16-23)

**Goal**: Конфігурований BPMN-подібний workflow engine з візуальним редактором.

#### Week 16-17: Workflow Domain Model
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

#### Week 18-19: Process Execution Engine
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

#### Week 20-21: Workflow API + Integration
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

#### Week 22-23: Frontend — Workflow Designer
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

### PHASE 4: Resource Registry + Directories (Weeks 24-29)

**Goal**: Універсальний реєстр ресурсів та конфігуровані довідники.

#### Week 24-25: Directory Service (Configurable Catalogs)
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

#### Week 26-27: Resource Registry
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

#### Week 28-29: Frontend — Resources + Directories
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

### PHASE 5: Search + Notifications + Webhooks (Weeks 30-35)

**Goal**: Elasticsearch full-text search, notification system, webhooks.

#### Week 30-31: Elasticsearch Integration
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

#### Week 32-33: Notification Service
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

#### Week 34-35: Webhook System
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

### PHASE 6: GraphQL + gRPC + API Maturity (Weeks 36-39)

**Goal**: Додаткові API protocols, API versioning, rate limiting.

#### Week 36-37: GraphQL API
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

#### Week 38-39: gRPC (Inter-service Communication)
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

### PHASE 7: Microservices Extraction (Weeks 40-45)

**Goal**: Розділити модульний моноліт на реальні мікросервіси.

#### Week 40-41: Notification Service extraction
- [ ] Окремий Symfony проект для Notification Service
- [ ] Окремий Docker container + окрема БД
- [ ] Комунікація: RabbitMQ (async) + gRPC (sync)
- [ ] Окремий CI/CD pipeline
- [ ] API Gateway routing (Traefik)

#### Week 42-43: Search Service extraction
- [ ] Окремий lightweight сервіс (можна навіть не Symfony — plain PHP або Go для досвіду)
- [ ] Consumes events from RabbitMQ → indexes in Elasticsearch
- [ ] Exposes search gRPC + REST API
- [ ] Traefik routing

#### Week 44-45: Infrastructure hardening
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

### PHASE 8: Frontend Polish + Advanced Features (Weeks 46-52)

**Goal**: Фінальна полірування, advanced features, production readiness.

#### Week 46-47: Dashboard + Analytics
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

#### Week 48-49: Advanced Workflow Features
- [ ] Process templates (pre-built common workflows):
  - Approval flow (request → approve/reject)
  - Inspection flow (schedule → inspect → report → approve)
  - Onboarding flow (HR → IT → Manager → Employee)
- [ ] Process variables (input/output data flowing through the process)
- [ ] Form builder for TaskNodes (custom forms per workflow step)
- [ ] Sub-process support (process within a process)
- [ ] Process analytics (avg duration, bottleneck nodes)

#### Week 50-51: OAuth2 + External Integrations
- [ ] OAuth2 Provider (allow external apps to authenticate)
- [ ] OAuth2 Client (login via Google, GitHub)
- [ ] API keys for external integrations
- [ ] Telegram bot notification channel (optional)
- [ ] Export: CSV, PDF reports

#### Week 52: Final Polish
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
│   │   └── FileStorage/         # MinIO/S3 abstraction
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
│   │   │   └── search/
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
