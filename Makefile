.PHONY: install docker-up test coverage lint lint-fix analyse check hooks

# If tsqm-php-cli container is running — execute inside it, otherwise — locally
DOCKER_EXEC := $(shell docker inspect -f '{{.State.Running}}' tsqm-php-cli 2>/dev/null | grep -q true && echo 'docker exec tsqm-php-cli')

# Start docker services
docker-up:
	docker compose up

# Install dependencies and git hooks
install:
	$(DOCKER_EXEC) composer install && $(MAKE) hooks

# Run tests (PHPUnit)
test:
	$(DOCKER_EXEC) composer test

# Run tests with coverage
coverage:
	$(DOCKER_EXEC) composer coverage

# Linter (php-cs-fixer, dry-run)
lint:
	$(DOCKER_EXEC) composer lint

# Autoformat (php-cs-fixer)
lint-fix:
	$(DOCKER_EXEC) composer lint-fix

# Static analysis (PHPStan)
analyse:
	$(DOCKER_EXEC) composer analyse

# Tests + lint + analyse
check:
	$(DOCKER_EXEC) composer check

# Install git hooks
hooks:
	$(DOCKER_EXEC) composer hooks
