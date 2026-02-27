# External Integrations

**Analysis Date:** 2026-02-27

## APIs & External Services

**Message Broker:**
- RabbitMQ 4.2 - Async command processing, domain event distribution, timer/webhook execution
  - SDK/Client: symfony/amqp-messenger 8.0.*, symfony/messenger 8.0.*
  - Connection: `MESSENGER_TRANSPORT_DSN` env var (AMQP protocol)
  - Routes: Task events (TaskAssignedEvent, TaskStatusChangedEvent, CommentAddedEvent) to `async` transport
  - Routes: Workflow timers (FireTimerMessage), webhooks (ExecuteWebhookMessage), notifications to `async`
  - Retry strategy: 3 attempts with exponential backoff (1s → 2s → 4s, max 60s)

**Real-time Updates:**
- Mercure v2.10 - Server-sent events for live UI updates
  - SDK/Client: symfony/mercure-bundle 0.4.*, symfony/mercure 0.7.*
  - Connection: `MERCURE_URL` (internal), `MERCURE_PUBLIC_URL` (browser)
  - Auth: JWT signing with `MERCURE_JWT_SECRET`
  - Usage: Task status changes published by `App\TaskManager\Infrastructure\Mercure\TaskMercurePublisher`
  - CORS configured for http://localhost:5173 (frontend) and http://localhost:8080 (API)

**Webhooks (Outgoing):**
- Workflow webhook nodes fire HTTP POST requests to configured external services
  - Implementation: `App\Workflow\Infrastructure\Webhook\ExecuteWebhookMessage` (async handler)
  - Uses Symfony HTTP client for requests
  - Supports custom headers and body transformation via workflow node config

## Data Storage

**Databases:**
- PostgreSQL 18-alpine (primary) at postgres:5432
  - Connection: `DATABASE_URL` env var
  - Client: Doctrine ORM 3.6 via symfony/doctrine-bundle 3.2
  - Mappings: XML-based in `backend/src/[Module]/Infrastructure/Persistence/Doctrine/Mapping/`
    - Identity (User, RefreshToken)
    - Organization (Organization, Department, Position, Employee, EmployeeRole, Invitation)
    - TaskManager (Task, Board, BoardColumn, Comment, Label, TaskAssignment, TaskAttachment)
    - Workflow (ProcessDefinition, Node, Transition, ProcessDefinitionVersion, ProcessInstance, WorkflowTaskLink, EventStore)
    - Resource, Directory, Notification modules
  - Type: UUID (Symfony bridge type), JSONB for dynamic fields (Directories, Resources)
  - Migrations: Doctrine Migrations 4.0 in `backend/migrations/` (Version20260226180000.php, etc.)

**Cache & Session:**
- Redis 8-alpine at redis:6379
  - Connection: `REDIS_URL` env var
  - Client: Redis PHP extension (native)
  - Usage:
    - Session storage (Symfony session handler)
    - Cache backend (app: cache.adapter.redis)
    - Refresh tokens (stored as plain Redis keys in `App\Identity\Infrastructure\Security\RefreshTokenService`)
    - Doctrine query/result caching (production only)

**Event Store:**
- PostgreSQL (same instance) — Event Sourcing for Workflow ProcessInstances
  - Table: event_store (via `App\Workflow\Infrastructure\EventStore\DoctrineEventStore`)
  - Events: ProcessStartedEvent, TaskNodeActivatedEvent, NotificationNodeActivatedEvent, WebhookNodeActivatedEvent, etc.
  - Serialization: PHP serialization via `App\Workflow\Infrastructure\EventStore\EventSerializer`

**File Storage:**
- LocalStack S3 (development) at localstack:4566
  - Production: AWS S3 (configure AWS_ENDPOINT to real S3 endpoint)
  - SDK/Client: league/flysystem-aws-s3-v3 3.32, aws/aws-sdk-php (implicit dependency)
  - Implementation: `App\TaskManager\Infrastructure\Storage\S3FileStorage`
  - Configuration:
    - `AWS_ENDPOINT` (internal, for uploads)
    - `AWS_ENDPOINT_PUBLIC` (external, for browser access)
    - `AWS_REGION` (eu-central-1 by default)
    - `AWS_BUCKET` (procivo by default)
    - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
  - Pre-signed URLs: Generated for 1-hour access window
  - Usage: Task attachments

**Caching:**
- Redis (described above) — application cache + session storage
- Doctrine caching pools (production only): query_cache_pool, result_cache_pool

## Authentication & Identity

**Auth Provider:**
- Custom JWT + Refresh Token (no external OAuth)
  - Implementation: Lexik JWT Authentication Bundle 3.2
  - Token format: RSA-256 signed JWT
  - Access token TTL: 1 hour (token_ttl: 3600)
  - Keys: Private key at `backend/config/jwt/private.pem`, public key at `backend/config/jwt/public.pem`
  - Refresh token: Redis-backed with rotation (30-day TTL)
  - Extraction: Authorization header with "Bearer " prefix

**Endpoints:**
- `POST /api/v1/auth/register` - User registration (public, rate-limited 3/hour)
- `POST /api/v1/auth/login` - User login (public, rate-limited 5/15min)
- `POST /api/v1/auth/refresh` - Token refresh (public)
- `POST /api/v1/auth/logout` - Token revocation (authenticated)
- `GET /api/v1/auth/me` - Current user profile (authenticated)
- `PATCH /api/v1/auth/password` - Password change (authenticated)

**Providers:**
- SecurityUserProvider: `App\Identity\Infrastructure\Security\SecurityUserProvider`
- Password hashing: Symfony auto (Argon2i/Bcrypt)
- User entity: `App\Identity\Domain\Entity\User` (domain model, clean from Symfony UserInterface)

## Monitoring & Observability

**Error Tracking:**
- Not detected — logs only (see below)

**Logs:**
- Monolog 4.x (symfony/monolog-bundle)
  - Configuration: `backend/config/packages/monolog.yaml`
  - Output: stderr for containerization
  - Development: all levels
  - Production: warning+ levels

**Request Documentation:**
- NelmioApiDocBundle 5.9 - Swagger/OpenAPI generation
  - Endpoint: `/api/doc` (public access)
  - Auto-generates API docs from controller annotations

## CI/CD & Deployment

**Hosting:**
- Docker Compose (development) - See docker-compose.yml
- Docker containers (production-ready structure)
- Kubernetes-ready (service ports, healthchecks, healthz endpoints planned)

**CI Pipeline:**
- Not detected in current codebase
- GitHub Actions workflow planned (pre-commit hooks setup detected but no CI YAML)
- Pre-commit hooks setup: likely for lint/format checks

## Environment Configuration

**Required env vars (Backend):**
- `APP_ENV` - Environment (dev|test|prod)
- `APP_SECRET` - Symfony session encryption secret
- `DATABASE_URL` - PostgreSQL connection string
- `MESSENGER_TRANSPORT_DSN` - RabbitMQ AMQP URL
- `MAILER_DSN` - SMTP server URL
- `MAILER_FROM` - Sender email address
- `REDIS_URL` - Redis connection URL
- `REDIS_HOST`, `REDIS_PORT` - Redis host/port (alternate format)
- `MERCURE_URL` - Internal Mercure hub URL
- `MERCURE_PUBLIC_URL` - Browser-accessible Mercure URL
- `MERCURE_JWT_SECRET` - JWT signing secret for Mercure
- `AWS_ENDPOINT` - S3 endpoint (internal)
- `AWS_ENDPOINT_PUBLIC` - S3 endpoint (public/browser)
- `AWS_REGION` - AWS region
- `AWS_BUCKET` - S3 bucket name
- `AWS_ACCESS_KEY_ID` - AWS credentials
- `AWS_SECRET_ACCESS_KEY` - AWS credentials
- `JWT_SECRET_KEY` - Path to JWT private key
- `JWT_PUBLIC_KEY` - Path to JWT public key
- `JWT_PASSPHRASE` - Passphrase for private key (optional)
- `FRONTEND_URL` - Frontend base URL (for invitation email links)
- `DEFAULT_URI` - Default API URL (http://localhost:8080)

**Frontend env vars:**
- `VITE_API_BASE_URL` - Backend API base URL (defaults to localhost:8080/api/v1)
- `VITE_MERCURE_URL` - Mercure public URL for real-time updates

**Secrets location:**
- `.env` files (development only, NOT committed in production)
- Sensitive keys in Docker secrets or Kubernetes Secrets (production)
- JWT keys: `backend/config/jwt/` directory (private key NOT committed)

## Webhooks & Callbacks

**Incoming:**
- Workflow webhook nodes accept HTTP POST responses
- Configured via workflow designer (target URL, request body template)
- Processed asynchronously via RabbitMQ

**Outgoing:**
- Task update notifications via Mercure (publish topic: `/organizations/{orgId}/tasks/{taskId}`)
- Workflow task activation via internal messaging (no external webhooks for now)

**Email Callbacks:**
- Invitation emails sent via Mailpit SMTP (development)
- No callback webhooks, but uses async job queue (RabbitMQ)

---

*Integration audit: 2026-02-27*
