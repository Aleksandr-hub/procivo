DOCKER_COMPOSE = docker compose
PHP = $(DOCKER_COMPOSE) exec php
CONSOLE = $(PHP) bin/console
COMPOSER = $(PHP) composer

.PHONY: help up down build restart bash logs test lint fix stan cc

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

# === Docker ===
up: ## Start all containers
	$(DOCKER_COMPOSE) up -d

down: ## Stop all containers
	$(DOCKER_COMPOSE) down

build: ## Build containers
	$(DOCKER_COMPOSE) build

restart: ## Restart all containers
	$(DOCKER_COMPOSE) restart

bash: ## Open PHP container shell
	$(DOCKER_COMPOSE) exec php bash

logs: ## Tail container logs
	$(DOCKER_COMPOSE) logs -f

ps: ## Show running containers
	$(DOCKER_COMPOSE) ps

# === PHP / Symfony ===
test: ## Run PHPUnit tests
	$(PHP) vendor/bin/phpunit

lint: ## Run PHP CS Fixer (dry-run)
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

fix: ## Run PHP CS Fixer (fix)
	$(PHP) vendor/bin/php-cs-fixer fix

stan: ## Run PHPStan
	$(PHP) vendor/bin/phpstan analyse

cc: ## Clear Symfony cache
	$(CONSOLE) cache:clear

migrate: ## Run database migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

diff: ## Generate migration diff
	$(CONSOLE) doctrine:migrations:diff

# === Composer ===
install: ## Composer install
	$(COMPOSER) install

update: ## Composer update
	$(COMPOSER) update

require: ## Composer require (usage: make require p=package/name)
	$(COMPOSER) require $(p)

require-dev: ## Composer require --dev (usage: make require-dev p=package/name)
	$(COMPOSER) require --dev $(p)
