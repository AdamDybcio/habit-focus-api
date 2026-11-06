.DEFAULT_GOAL := help
.PHONY: help up down serve jwt-keys db-create db-migrate db-diff db-reset test test-unit test-functional lint cs cs-fix stan check

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-18s %s\n", $$1, $$2}'

up: ## Start the development database
	docker compose up -d --wait database

down: ## Stop the development database
	docker compose down

serve: ## Run the API on http://127.0.0.1:8000
	php -S 127.0.0.1:8000 -t public

jwt-keys: ## Generate JWT keys (uses JWT_PASSPHRASE from .env / .env.local)
	php bin/console lexik:jwt:generate-keypair --skip-if-exists

db-create: ## Create the dev and test databases
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:database:create --if-not-exists --env=test

db-migrate: ## Apply migrations (dev and test databases)
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console doctrine:migrations:migrate --no-interaction --env=test

db-diff: ## Generate a migration from entity changes
	php bin/console doctrine:migrations:diff

db-reset: ## Drop and recreate the dev database
	php bin/console doctrine:database:drop --force --if-exists
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate --no-interaction

test: ## Run all tests
	vendor/bin/phpunit

test-unit: ## Run unit tests only
	vendor/bin/phpunit --testsuite unit

test-functional: ## Run functional (HTTP + database) tests only
	vendor/bin/phpunit --testsuite functional

lint: ## Lint container, YAML and Doctrine mapping
	php bin/console lint:container
	php bin/console lint:yaml config
	php bin/console doctrine:schema:validate --skip-sync

cs: ## Check code style (no changes)
	vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix code style
	vendor/bin/php-cs-fixer fix

stan: ## Static analysis (PHPStan level 8)
	php bin/console cache:warmup --env=dev
	vendor/bin/phpstan analyse --memory-limit=512M

check: lint cs stan test ## Everything CI runs
