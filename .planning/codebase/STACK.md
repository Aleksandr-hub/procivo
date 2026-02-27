# Technology Stack

**Analysis Date:** 2026-02-27

## Languages

**Primary:**
- PHP 8.4 - Backend (Symfony 8.0 application)
- TypeScript 5.9 - Frontend (Vue 3 + Vite)
- JavaScript - Frontend tooling and build scripts

**Secondary:**
- YAML - Configuration (Symfony config)
- SQL - Database migrations and queries
- HTML/CSS - Templates (Vue SFCs)
- SCSS - Styling via sass-embedded

## Runtime

**Environment:**
- PHP-FPM (Docker container) - Backend runtime at `./docker/php/`
- Node.js 20.19.0+ or 22.12.0+ - Frontend development and build
- Nginx 1.28-alpine - HTTP gateway (port 8080)

**Package Manager:**
- Composer 2.x - PHP dependencies in `backend/composer.json`
  - Lockfile: `backend/composer.lock` (committed)
- npm - JavaScript/frontend dependencies in `frontend/package.json`
  - Lockfile: `frontend/package-lock.json` (committed)

## Frameworks

**Core:**
- Symfony 8.0.* - PHP framework with bundles for routing, security, messenger, doctrine
- Vue 3.5.* - Frontend SPA framework
- Vite 7.3.* - Frontend build tool and dev server (port 5173)

**Testing:**
- PHPUnit 13.0 - Backend unit and integration testing
- Vitest 4.0.* - Frontend unit testing (config: `frontend/vitest.config.ts`)
- @vue/test-utils 2.4.* - Vue component testing

**Build/Dev:**
- Vite 7.3.* with @vitejs/plugin-vue 6.0.* - Vue SFC compilation
- TypeScript 5.9.* - Type checking (via vue-tsc via `type-check` script)
- ESLint 9.39.* - JavaScript linting with @vue/eslint-config-typescript
- Oxlint 1.47.* - Fast JavaScript/TypeScript linting
- Prettier 3.8.1 - Code formatting
- PHP CS Fixer 3.94 - PHP code style fixing
- PHPStan 2.1 - PHP static analysis

## Key Dependencies

**Critical Backend:**
- symfony/framework-bundle 8.0.* - Core request/response handling
- symfony/messenger 8.0.* - CQRS buses (command, query, event) with RabbitMQ transport
- symfony/security-bundle 8.0.* - Authentication and authorization
- doctrine/doctrine-bundle 3.2 + doctrine/orm 3.6 - ORM with XML mappings
- symfony/translation 8.0.* - i18n (translations in `backend/translations/`)
- symfony/mailer 8.0.* + symfony/amqp-messenger 8.0.* - Email delivery via Mailpit
- symfony/mercure-bundle 0.4.* - Real-time updates (Mercure protocol)

**Authentication & Authorization:**
- lexik/jwt-authentication-bundle 3.2 - JWT tokens with RSA-256 (1h TTL)
- symfony/rate-limiter 8.0.* - Rate limiting on /auth/login (5/15min) and /auth/register (3/hour)

**Workflow & Persistence:**
- symfony/workflow 8.0.* - Task state machine (7 states: draft, open, in_progress, review, done, blocked, cancelled)
- doctrine/doctrine-migrations-bundle 4.0 - Database migrations in `backend/migrations/`
- league/flysystem-aws-s3-v3 3.32 - S3 file storage abstraction

**Infrastructure:**
- symfony/expression-language 8.0.* - Expression evaluation for workflow conditions
- symfony/validator 8.0.* - Input validation
- symfony/serializer 8.0.* - JSON serialization for APIs

**Critical Frontend:**
- primevue 4.5.* - UI component library with @primevue/themes 4.5.*
- pinia 3.0.* - State management (stores for tasks, workflows, organizations)
- vue-router 5.0.* - Client-side routing
- axios 1.13.* - HTTP client with JWT refresh token handling in `frontend/src/shared/api/http-client.ts`
- vue-i18n 12.0.0-alpha.3 - Frontend i18n (locales in `frontend/src/i18n/locales/`)
- zod 4.3.* - Runtime schema validation
- @vue-flow/* 1.x - Workflow designer canvas (core, background, controls, minimap)

**Frontend Tooling:**
- unplugin-vue-components 31.0.* - Auto component registration
- @primevue/auto-import-resolver 4.5.* - PrimeVue component auto-import
- vite-plugin-vue-devtools 8.0.* - Vue DevTools integration

## Configuration

**Environment:**
- Backend: `backend/.env` (default) + environment-specific overrides
  - `APP_ENV` (dev|test|prod)
  - Database: `DATABASE_URL` (PostgreSQL)
  - Message broker: `MESSENGER_TRANSPORT_DSN` (RabbitMQ AMQP)
  - Mailer: `MAILER_DSN` (SMTP to Mailpit)
  - Redis: `REDIS_URL`, `REDIS_HOST`, `REDIS_PORT`
  - Mercure: `MERCURE_URL`, `MERCURE_PUBLIC_URL`, `MERCURE_JWT_SECRET`
  - AWS S3: `AWS_ENDPOINT`, `AWS_ENDPOINT_PUBLIC`, `AWS_REGION`, `AWS_BUCKET`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
  - JWT: `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` (keys in `backend/config/jwt/`)
  - Frontend URL: `FRONTEND_URL` (for invitation links)
  - Mailer sender: `MAILER_FROM`

- Frontend: `frontend/.env` (not committed - uses `VITE_API_BASE_URL`)

**Build:**
- Backend: `backend/config/` (Symfony YAML configuration)
  - `services.yaml` - Service container with 3 named buses (command.bus, query.bus, event.bus) and repository aliases
  - `packages/doctrine.yaml` - 7 Doctrine mappings (Identity, Organization, TaskManager, Workflow, Resource, Directory, Notification)
  - `packages/messenger.yaml` - Async routing for domain events and workflow messages
  - `packages/lexik_jwt_authentication.yaml` - JWT extraction from Authorization header
  - `packages/security.yaml` - Firewall rules (API stateless JWT auth, public endpoints for auth/invitations/docs)
  - `packages/framework.yaml` - Cache (Redis), session handler (Redis), rate limiters

- Frontend:
  - `vite.config.ts` - Build config with Vue + devtools + auto-import (PrimeVue resolver)
  - `vitest.config.ts` - Test runner config
  - `tsconfig.json` - TypeScript strict mode
  - `eslint.config.ts` - ESLint rules (Vue + TypeScript)

## Platform Requirements

**Development:**
- Docker + Docker Compose (all services containerized)
- PHP 8.4 (or Docker image)
- Node.js 20.19.0+ or 22.12.0+
- Git for version control

**Services (Docker):**
- PostgreSQL 18-alpine - Database (port 5436)
- Redis 8-alpine - Cache/sessions (port 6380)
- RabbitMQ 4.2-management-alpine - Message broker (port 5672, mgmt 15672)
- Mercure latest - Real-time updates (port 3000)
- LocalStack - S3 emulation (port 4566) — MinIO archived Feb 2026
- Mailpit - SMTP + email UI (ports 1026/8026)
- Nginx 1.28-alpine - HTTP gateway (port 8080)

**Production:**
- Kubernetes or Docker Swarm for orchestration
- AWS S3 (replaces LocalStack)
- Production SMTP service (replaces Mailpit)
- PostgreSQL production instance
- Redis production cluster
- RabbitMQ production cluster
- Mercure production hub

---

*Stack analysis: 2026-02-27*
