# Procivo — BPM Platform

Business process management platform built on BPMN 2.0. Design workflows in a visual editor, publish them, and let users execute process instances with task forms and gateway routing.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) + Docker Compose
- [Node.js 22+](https://nodejs.org/) (for frontend dev server and ESLint pre-commit hook)
- [lefthook](https://github.com/evilmartians/lefthook) — pre-commit hooks (`brew install lefthook` or `npm install -g lefthook`)

## Quick Start

1. Clone the repository:
   ```bash
   git clone <repo-url> procivo && cd procivo
   ```

2. Copy environment template:
   ```bash
   cp backend/.env.example backend/.env
   ```

3. Generate JWT keys:
   ```bash
   mkdir -p backend/config/jwt
   openssl genpkey -algorithm RSA -out backend/config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
   openssl rsa -pubout -in backend/config/jwt/private.pem -out backend/config/jwt/public.pem
   ```

4. Start all services:
   ```bash
   docker compose up -d
   ```

5. Install backend dependencies:
   ```bash
   docker compose exec php composer install
   ```

6. Run database migrations:
   ```bash
   docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
   ```

7. Seed default roles:
   ```bash
   docker compose exec php php bin/console app:seed-roles
   ```

8. Start the frontend dev server:
   ```bash
   cd frontend && npm install && npm run dev
   ```

9. Set up pre-commit hooks:
   ```bash
   lefthook install
   ```

10. Open the app:
    - Frontend: http://localhost:5173
    - API: http://localhost:8080

## Development

| Service | URL |
|---|---|
| Frontend (Vite dev server) | http://localhost:5173 |
| Backend API | http://localhost:8080/api/v1/ |
| Mailpit (email UI) | http://localhost:8026 |
| RabbitMQ management | http://localhost:15672 (procivo/procivo) |
| Mercure hub | http://localhost:3000 |

## Running Tests

```bash
# Backend unit tests
docker compose exec php vendor/bin/phpunit

# Frontend unit tests
cd frontend && npm run test:unit
```

## Code Quality

```bash
# PHP code style (auto-fix)
docker compose exec php vendor/bin/php-cs-fixer fix

# PHP static analysis
docker compose exec php vendor/bin/phpstan analyse

# Frontend lint (auto-fix)
cd frontend && npm run lint

# Frontend type check
cd frontend && npm run type-check
```

Pre-commit hooks (via lefthook) run CS Fixer on staged `.php` files and ESLint on staged `.ts`/`.vue` files automatically.

## Tech Stack

- **Backend:** PHP 8.4, Symfony 8.0, Doctrine ORM, PostgreSQL 18
- **Frontend:** Vue 3, TypeScript, PrimeVue, Vite
- **Infrastructure:** Docker, RabbitMQ, Redis, Mercure (SSE), LocalStack (S3)
