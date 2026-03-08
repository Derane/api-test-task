.PHONY: help \
        up down build restart logs ps \
        php mysql mysql-root \
        install setup composer \
        cache-clear \
        doctrine-install migrate schema-create schema-update schema-validate \
        doctrine-check

COMPOSE  = docker compose
PHP      = $(COMPOSE) exec php
CONSOLE  = $(PHP) bin/console

# Colors
GREEN  = \033[0;32m
YELLOW = \033[0;33m
RESET  = \033[0m

##——— Help ————————————————————————————————————————————————————————————————————
help: ## Show available commands
	@printf "$(GREEN)Available commands:$(RESET)\n"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-22s$(RESET) %s\n", $$1, $$2}'

##——— Docker ——————————————————————————————————————————————————————————————————
up: ## Start all containers in detached mode
	$(COMPOSE) up -d

down: ## Stop and remove containers (keeps volumes)
	$(COMPOSE) down

down-v: ## Stop and remove containers AND volumes (deletes DB data!)
	$(COMPOSE) down -v

build: ## Build (or rebuild) Docker images without cache
	$(COMPOSE) build --no-cache

restart: ## Restart all containers
	$(COMPOSE) restart

logs: ## Follow logs of all containers
	$(COMPOSE) logs -f

logs-php: ## Follow PHP container logs only
	$(COMPOSE) logs -f php

ps: ## Show status of running containers
	$(COMPOSE) ps

##——— Shells ——————————————————————————————————————————————————————————————————
php: ## Enter PHP container shell (bash)
	$(COMPOSE) exec php bash

mysql: ## Enter MySQL shell as app user
	$(COMPOSE) exec mysql mysql -u $${MYSQL_USER:-app} -p$${MYSQL_PASSWORD:-app} $${MYSQL_DATABASE:-app}

mysql-root: ## Enter MySQL shell as root
	$(COMPOSE) exec mysql mysql -u root -p$${MYSQL_ROOT_PASSWORD:-root}

##——— Application —————————————————————————————————————————————————————————————
install: ## Install Composer dependencies
	$(PHP) composer install

composer: ## Run any composer command: make composer cmd="require vendor/pkg"
	$(PHP) composer $(cmd)

cache-clear: ## Clear Symfony cache
	$(CONSOLE) cache:clear

setup: build up install ## Full first-time setup: build → up → install
	@printf "$(GREEN)Setup complete! App running at http://localhost:$${NGINX_PORT:-8080}$(RESET)\n"

##——— Doctrine / Database ————————————————————————————————————————————————————
doctrine-install: ## Add Doctrine ORM + Migrations to the project
	$(PHP) composer require doctrine/doctrine-bundle doctrine/orm doctrine/doctrine-migrations-bundle

migrate: ## Run pending database migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

schema-create: ## Create database schema from entities (dev only)
	$(CONSOLE) doctrine:schema:create

schema-update: ## Update database schema to match entities (dev only)
	$(CONSOLE) doctrine:schema:update --force

schema-validate: ## Validate that schema matches entities
	$(CONSOLE) doctrine:schema:validate

doctrine-check: ## Check database connection
	$(CONSOLE) doctrine:query:sql "SELECT 1"
