# Architecture

**Analysis Date:** 2026-02-27

## Pattern Overview

**Overall:** Clean Architecture + Domain-Driven Design (DDD) with CQRS and Event Sourcing layers

**Key Characteristics:**
- Modular monolith with isolated bounded contexts (modules) → future microservices
- Clear separation: Domain → Application → Infrastructure → Presentation
- CQRS pattern: Command Bus and Query Bus with separate handlers
- Event-driven: Domain events emitted from aggregates, dispatched via Messenger
- Token-based state machine for Workflow module (custom BPMN engine)

## Layers

**Domain Layer:**
- Purpose: Pure business logic, rules, invariants, uncoupled from frameworks
- Location: `backend/src/{Module}/Domain/`
- Contains:
  - Entities (AggregateRoots): Rich domain objects with behavior
  - Value Objects: Immutable, identified by value (enums or classes)
  - Domain Events: Fired from aggregates, represent state changes
  - Repositories (interfaces): Data access contracts
  - Exceptions: Domain-specific errors
- Depends on: Nothing (no external dependencies)
- Used by: Application layer (handlers)

**Application Layer:**
- Purpose: Use case orchestration, input validation, DTO transformation
- Location: `backend/src/{Module}/Application/`
- Contains:
  - Commands: Explicit intent messages (create, update, delete)
  - Queries: Data retrieval requests
  - Handlers: Command/Query handlers decorated with `#[AsMessageHandler(bus: 'command.bus'|'query.bus')]`
  - DTOs: Data Transfer Objects for API responses
  - Event Handlers: Respond to domain events (subscribers)
  - Ports (interfaces): Adapters for external services (auth, mailers, etc.)
- Depends on: Domain
- Used by: Presentation (controllers), Domain event handlers

**Infrastructure Layer:**
- Purpose: Framework-specific implementations, external integrations
- Location: `backend/src/{Module}/Infrastructure/`
- Contains:
  - Repositories: Doctrine ORM implementations
  - Persistence: Doctrine mappings (XML), database adapters
  - Storage: S3/LocalStack file operations
  - Security: JWT, hashing, auth adapters
  - Service: External integrations (mail, Mercure, webhooks)
  - Event Store: Event Sourcing persistence (ProcessInstance uses event reconstruction)
  - Timer Service: RabbitMQ-based async task scheduling
- Depends on: Domain, Application
- Used by: Presentation, DI container

**Presentation Layer:**
- Purpose: HTTP API entry points, request handling, response formatting
- Location: `backend/src/{Module}/Presentation/`
- Contains:
  - Controllers: REST API endpoints with `#[Route]` attributes
  - Security: Permission checks, role guards
  - Console: CLI commands
- Depends on: Application, Domain
- Used by: HTTP router, CLI

## Modules (Bounded Contexts)

**Shared:**
- Domain base classes (AggregateRoot, DomainEvent, Repository)
- Bus interfaces and implementations (CommandBus, QueryBus, EventBus)
- Messenger middleware (DispatchDomainEventsMiddleware)
- Custom Doctrine types

**Identity:**
- User registration, authentication, JWT tokens
- Refresh token rotation (Redis-backed)
- Password hashing

**Organization:**
- Organizational structure: Organization → Department → Position → Employee
- Role-based access control (RBAC)
- Employee assignments with hierarchical relationships
- Invitation workflow
- Permissions model

**TaskManager:**
- Task entity with status state machine (Draft → Open → InProgress → Review → Done)
- Kanban boards with columns
- Labels, comments, attachments
- Task assignments with strategy resolution (specific user, by role, by department)
- Task claiming (queue pooling)
- S3 file storage integration

**Workflow:**
- Process definitions (BPMN-like models) with versioning
- Nodes: Start, End, Task, Gateways (Exclusive/Parallel/Inclusive), Timer, Notification, Webhook, SubProcess
- Transitions with condition expressions
- ProcessInstance: Event Sourced aggregate, token-based execution
- Token flow through graph; process state reconstructed from event stream
- WorkflowTaskLink: Bridge between Workflow and TaskManager (process → task mapping)
- Integration: TaskNodeActivated event creates/updates tasks via command dispatch

**Notification:**
- Notification entity with read/unread tracking
- Event-driven: Listens to task events (TaskAssigned, TaskStatusChanged)
- Real-time updates via Mercure

**Resource & Directory:**
- Generic resource management
- Directory services (stub implementations for future extensibility)

**Search:**
- Elasticsearch integration (infrastructure layer only)

## Data Flow

### Command Execution Flow (e.g., CreateTask)
1. HTTP POST → `TaskController::create()`
2. Controller decodes JSON, authorizes, dispatches `CreateTaskCommand`
3. Command Bus (Messenger) routes to `CreateTaskHandler`
4. Handler creates Task entity, calls `Task::create()`
5. Entity records `TaskCreatedEvent` internally
6. Repository saves entity to database
7. **DispatchDomainEventsMiddleware** extracts events from UnitOfWork
8. Events dispatched via EventBus → async/sync handlers
9. Response returned to client

### Event Handling (e.g., TaskNodeActivated)
1. Domain event raised: `TaskNodeActivatedEvent`
2. Dispatched via EventBus (async routing via Messenger)
3. `OnTaskNodeActivated` handler triggered (decorated with `#[AsMessageHandler(bus: 'event.bus')]`)
4. Handler dispatches 0+ commands (CreateTask, UpdateTask, TransitionTask)
5. Those commands create new domain events
6. Process continues cascading through event handlers

### Query Execution (e.g., ListTasks)
1. HTTP GET → `TaskController::list()`
2. Dispatches `ListTasksQuery` to Query Bus
3. `ListTasksHandler` executes (read-only, no side effects expected)
4. Returns data objects/DTOs
5. Controller serializes response

### Workflow → Task Integration
1. Workflow engine executes process; token reaches Task node
2. `TaskNodeActivatedEvent` emitted
3. `OnTaskNodeActivated` handler:
   - Reads process variables
   - Resolves task configuration (title template, assignment strategy)
   - Dispatches `CreateTaskCommand` (or `UpdateTaskCommand` if reusing)
   - Creates `WorkflowTaskLink` to store process ↔ task mapping
4. Task created in TaskManager module
5. User interacts with task; action triggered
6. `ExecuteTaskActionCommand` dispatches
7. Handler updates task, evaluates conditions
8. Commands/events loop back to Workflow engine (via event handlers)

**State Management (Frontend):**
- Pinia stores per module: `auth.store`, `task.store`, `process-instance.store`
- API calls via dedicated client modules: `task.api`, `process-definition.api`
- HTTP client with token refresh interceptor and retry logic
- Router manages navigation and URL state

## Entry Points

**Backend HTTP:**
- Location: `backend/src/*/Presentation/Controller/`
- Routes: `#[Route('/api/v1/...')]` attributes on controller classes
- Example: `TaskController::create()` → `POST /api/v1/organizations/{orgId}/tasks`

**Backend CLI:**
- Location: `backend/src/*/Presentation/Console/`
- Command: `php bin/console {command}` (e.g., seed roles)

**Kernel:**
- Location: `backend/src/Kernel.php`
- MicroKernel with trait-based routing

**Frontend Router:**
- Location: `frontend/src/router/index.ts`
- Root layout: `DashboardLayout.vue`
- Protected routes require auth metadata
- Nested routes for org-scoped resources (tasks, workflows, etc.)

**Frontend Entry Points (Pages):**
- Organization hub: `/organizations` → `OrganizationsPage.vue`
- Organization detail: `/organizations/:orgId` → `OrganizationDetailPage.vue`
- Tasks: `/organizations/:orgId/tasks` → `TasksPage.vue` (master-detail)
- Kanban: `/organizations/:orgId/boards/:boardId/kanban`
- Workflow designer: `/organizations/:orgId/process-definitions/:definitionId/designer`
- Process instances: `/organizations/:orgId/process-instances`

## Key Abstractions

**AggregateRoot:**
- Base class for domain entities: `backend/src/Shared/Domain/AggregateRoot.php`
- Provides `recordEvent()`, `pullDomainEvents()` lifecycle
- Used by: Task, ProcessInstance, Organization, Employee, etc.
- Pattern: Entities emit events, middleware extracts and dispatches

**Bus Interfaces:**
- `CommandBusInterface`: `dispatch(CommandInterface $command): void` (void return, imperative)
- `QueryBusInterface`: `ask(QueryInterface $query): mixed` (returns result)
- `EventBusInterface`: `dispatch(DomainEvent $event): void`
- Implementations: Messenger wrappers (`MessengerCommandBus`, etc.)

**Repository Pattern:**
- Domain interface: `{Module}/Domain/Repository/{Entity}RepositoryInterface`
- Infrastructure implementation: `{Module}/Infrastructure/Repository/Doctrine{Entity}Repository`
- DI configured in `services.yaml`: interface → implementation alias
- Example: `TaskRepositoryInterface` → `DoctrineTaskRepository`

**Port Pattern:**
- Application layer defines: `{Module}/Application/Port/{PortName}Interface`
- Infrastructure provides: `{Module}/Infrastructure/{Service}/{Implementation}`
- Examples:
  - `FileStorageInterface` → `S3FileStorage`
  - `PasswordHasherInterface` → `SymfonyPasswordHasher`
  - `JwtTokenManagerInterface` → `LexikJwtTokenManager`

**Value Objects:**
- Immutable: PHP enums (TaskStatus, TaskPriority, AssignmentStrategy)
- Immutable classes: TaskId, BoardId, etc. with `fromString()`, `generate()` constructors
- Pattern: Entities store primitives internally, expose VOs via getters (Doctrine compatibility)

**DTOs (Data Transfer Objects):**
- Location: `{Module}/Application/DTO/`
- Read-only classes with `static fromEntity()` constructor
- Example: `TaskDTO::fromEntity(Task $task, array $transitions): TaskDTO`
- Used for API responses, decoupling domain from serialization

**Domain Events:**
- Location: `{Module}/Domain/Event/`
- Implement `DomainEvent` interface
- Properties are readonly, constructor-initialized
- Include `occurredAt()` and `eventName()` methods
- Serialized by EventStore or dispatched async via Messenger

## Error Handling

**Strategy:** Exception-based with domain-specific custom exceptions

**Domain Exceptions:**
- Located: `{Module}/Domain/Exception/`
- Example: `TaskClaimException`, `WorkflowExecutionException`
- Thrown from domain logic when invariants violated

**Framework Exceptions:**
- Symfony exceptions: `Symfony\Component\HttpKernel\Exception\{BadRequest, NotFound, Forbidden}`
- Used by controllers for HTTP error responses

**Patterns:**
- Domain layer throws domain exceptions
- Handlers catch, transform to framework exceptions if needed
- Controllers convert exceptions to JSON responses (error middleware)
- Frontend receives error status codes, displays localized messages

## Cross-Cutting Concerns

**Logging:**
- Not explicitly configured in provided files
- Convention: Use Monolog (Symfony default)
- Approach: Log at boundary points (controller entry, command dispatch, failures)

**Validation:**
- Input validation: Controllers validate decoded JSON
- Domain validation: Entities check invariants in constructor/factory methods
- Framework validation: Symfony validation attributes (not yet in domain)

**Authentication:**
- JWT via `LexikJwtAuthenticationBundle` v3.2
- RSA-256 signature
- Token claim includes user ID
- SecurityUserProvider adapts domain User to Symfony UserInterface

**Authorization:**
- Role-based (Organization module): Employee → Role → Permission
- Method-level: `OrganizationAuthorizer::authorize($orgId, 'TASK_CREATE')`
- Guards organization scope (users can only access their org resources)

**Transactions:**
- Doctrine transaction middleware on command.bus
- All command handling wraps in transaction; events dispatched post-commit
- Ensures consistency: either entire command succeeds (with events) or rolls back

**Async Processing (Messenger):**
- Command Bus: Sync with transaction wrapper
- Event Bus: Async (RabbitMQ) for non-blocking operations
  - TaskAssignedEvent → async (notifications)
  - ProcessStartedEvent → sync (immediate effects)
- Custom routing in `messenger.yaml`: specific events → specific transports
- Retry strategy: 3 attempts, exponential backoff

**Real-time Updates:**
- Mercure v2.10 integration
- TaskController publishes Mercure updates on task changes
- Frontend subscribes to updates (managed by Pinia in store)
- Example: Task status change broadcasts to all viewers

---

*Architecture analysis: 2026-02-27*
