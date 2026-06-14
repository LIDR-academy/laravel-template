# =============================================================================
#  Laravel Template — Developer Experience (DX) commands
#  Run `make` or `make help` to see all available targets.
# =============================================================================

# Align the container user with the host user (prevents permission issues on Linux).
export UID := $(shell id -u)
export GID := $(shell id -g)

DC      := docker compose
APP     := $(DC) exec app
APP_RUN := $(DC) run --rm app
ARGS    ?=

.DEFAULT_GOAL := help

# -----------------------------------------------------------------------------
#  Help
# -----------------------------------------------------------------------------
.PHONY: help
help: ## Show this help
	@echo "Laravel Template — available commands:"
	@echo ""
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'
	@echo ""

# -----------------------------------------------------------------------------
#  Bootstrap
# -----------------------------------------------------------------------------
.PHONY: setup
setup: ## First-time setup: env, build, start, install deps, key, migrate
	@test -f .env || (cp .env.example .env && echo "✓ .env created from .env.example")
	$(DC) build
	$(DC) up -d --wait
	$(APP) composer install
	@grep -q "^APP_KEY=base64" .env || $(APP) php artisan key:generate
	$(APP) php artisan migrate --force
	@echo ""
	@echo "✓ Setup complete. Services:"
	@echo "    App ........ http://localhost:8000"
	@echo "    Vite ....... http://localhost:5173"
	@echo "    Mailpit .... http://localhost:8025"
	@echo "    pgAdmin .... http://localhost:5050"

# -----------------------------------------------------------------------------
#  Container lifecycle
# -----------------------------------------------------------------------------
.PHONY: up
up: ## Start all containers in the background
	$(DC) up -d --wait

.PHONY: down
down: ## Stop and remove containers
	$(DC) down

.PHONY: restart
restart: down up ## Restart all containers

.PHONY: stop
stop: ## Stop containers without removing them
	$(DC) stop

.PHONY: build
build: ## Build the application image
	$(DC) build

.PHONY: rebuild
rebuild: ## Rebuild the image from scratch (no cache)
	$(DC) build --no-cache

.PHONY: ps
ps: ## Show running containers
	$(DC) ps

.PHONY: logs
logs: ## Tail logs from all containers (CTRL+C to exit)
	$(DC) logs -f

.PHONY: clean
clean: ## Stop containers and DELETE volumes (database, redis, pgadmin data)
	$(DC) down -v

# -----------------------------------------------------------------------------
#  Database
# -----------------------------------------------------------------------------
.PHONY: migrate
migrate: ## Run database migrations
	$(APP) php artisan migrate

.PHONY: rollback
rollback: ## Roll back the last migration batch
	$(APP) php artisan migrate:rollback

.PHONY: fresh
fresh: ## Drop all tables and re-run migrations + seeders
	$(APP) php artisan migrate:fresh --seed

.PHONY: seed
seed: ## Run database seeders
	$(APP) php artisan db:seed

.PHONY: psql
psql: ## Open a psql shell on the dev database
	$(DC) exec db psql -U laravel -d laravel

# -----------------------------------------------------------------------------
#  Development helpers
# -----------------------------------------------------------------------------
.PHONY: shell
shell: ## Open a bash shell inside the app container
	$(APP) bash

.PHONY: tinker
tinker: ## Open Laravel Tinker
	$(APP) php artisan tinker

.PHONY: artisan
artisan: ## Run an artisan command, e.g. `make artisan ARGS="route:list"`
	$(APP) php artisan $(ARGS)

.PHONY: composer
composer: ## Run a composer command, e.g. `make composer ARGS="require vendor/pkg"`
	$(APP) composer $(ARGS)

.PHONY: npm
npm: ## Run an npm command in the vite container, e.g. `make npm ARGS="install pkg"`
	$(DC) exec vite npm $(ARGS)

.PHONY: fmt
fmt: ## Format code with Laravel Pint
	$(APP) ./vendor/bin/pint

# -----------------------------------------------------------------------------
#  Testing  (uses the separate `laravel_test` database — never touches dev data)
# -----------------------------------------------------------------------------
.PHONY: test
test: ## Run the test suite (Pest)
	$(APP) php artisan test

.PHONY: test-filter
test-filter: ## Run tests matching a filter, e.g. `make test-filter ARGS="UserTest"`
	$(APP) php artisan test --filter=$(ARGS)
