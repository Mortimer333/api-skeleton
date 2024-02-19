help:
		@echo "cs-check                    Validating coding standards rules"
		@echo "cs-fix                      Fix coding standards"
		@echo "phpmd                       Run PHP Mess Detector"
		@echo "phpcpd                      Run Copy/Paste Detector"
		@echo "security_checker            Run SensioLabs Security Checker"
		@echo "phpstan                     Run PHPstan analyse"
		@echo "psalm                       Run Psalm analyse"
		@echo "analysis                    Run cs-check + phpmd + phpcpd + phpstan"
		@echo ""

		@echo "unit-test                   Run unit tests"
		@echo "integration-test             Run integration tests"
		@echo "api-test                    Run integration tests"
		@echo "test-all                    Run all tests"
		@echo "single-test                 Run single test"
		@echo ""

		@echo "before-push                 Run before pushing changes to origin"
		@echo "migrate                     Run migrations"
		@echo "reset-test-env              Purges test DB and repopulates it with fixtures"
		@echo ""

		@echo "entity                      Create entity"
		@echo "entity-test                 Create entity in test namespace"
		@echo ""

cs-check:
	@echo "Validating coding standards rules"
	php vendor/bin/phpcs --standard=PSR12 -s --colors --extensions=php src

cs-fix:
	@echo "Fix coding standards"
	php vendor/bin/php-cs-fixer fix \
	src/ \
	Tests/Support/Helper/ \
	Tests/Support/ApiTester.php \
	Tests/Support/IntegrationTester.php \
	Tests/Support/UnitTester.php \
	Tests/Api \
	Tests/Integration \
	Tests/Unit \
	migrations/ \
	--verbose --config=.php-cs-fixer.dist.php

phpstan:
	@echo "Runing PHPStan"
	php -d memory_limit=4G vendor/bin/phpstan analyse src Tests migrations

psalm:
	@echo "Runing Psalm"
	php -d memory_limit=4G vendor/bin/psalm --taint-analysis

phpmd:
	@echo "Runing PHP Mess Detector"
	php -d memory_limit=4G vendor/bin/phpmd src text phpmd.xml

analysis:
	$(MAKE) cs-check
	$(MAKE) phpstan
	$(MAKE) psalm
	$(MAKE) phpmd

# tests

unit-test:
	@echo "Runing Unit Tests"
	php vendor/bin/codecept run Unit $(args)

integration-test:
	@echo "Runing Integration Tests"
	php vendor/bin/codecept run Integration $(args)

api-test:
	@echo "Running API Tests"
	php vendor/bin/codecept run Api $(args)

coverage-test:
	@echo "Run all tests and get html coverage"
	XDEBUG_MODE=coverage php vendor/bin/codecept run --coverage-html

test-all:
	$(MAKE) unit-test
	$(MAKE) integration-test
	$(MAKE) api-test

single-test:
	bash ./dev/run_single_test.sh $(filter-out $@,$(MAKECMDGOALS))

before-push:
	$(MAKE) cs-fix
	$(MAKE) analysis
	$(MAKE) test-all

migrate:
	@echo "Running Migrate for current and test environment"
	php bin/console doctrine:migration:migrate --no-interaction --allow-no-migration
	APP_ENV=test php bin/console doctrine:migration:migrate --no-interaction --allow-no-migration

reset-test-db:
	@echo "Resetting test DB - 'There is no active transaction' is expected"
	APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction --group=test  --purger=test_purger

reset-dev-db:
	@echo "Resetting current DB"
	php bin/console doctrine:fixtures:load --no-interaction --group=dev --purger=dev_purger

tests-build:
	@echo "Build codeception generated settings and methods"
	php vendor/bin/codecept build

fix-test-permissions:
	@echo "Fix permissions to files in var folder"
	chown -R www-data var/

entity:
	php bin/console make:entity \\Shared\\Entity\\Api\\$(filter-out $@,$(MAKECMDGOALS))

entity-test:
	APP_ENV=test php bin/console make:entity \\Shared\\Entity\\Test\\$(filter-out $@,$(MAKECMDGOALS))
