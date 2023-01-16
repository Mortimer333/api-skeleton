# Internal

## Security Cheat Sheet

https://cheatsheetseries.owasp.org/index.html

- check leaked passwords - https://haveibeenpwned.com/Passwords

## Swagger

https://symfony.com/bundles/NelmioApiDocBundle/current/index.html

- routes will show on swagger only if name starts with `api.`

https://zircote.github.io/swagger-php/guide/attributes.html
  
https://github.com/nelmio/NelmioApiDocBundle/issues/1990
  
https://swagger.io/docs/specification/about/

## Security

https://symfony.com/doc/current/security.html

- #[CurrentUser] attribute doesn't work, use `Symfony\Component\Security\Core\Security::getUser()`
  instead - https://github.com/symfony/symfony/issues/40333

## CSRF Token

if we have api platform the csrf token should be generated from FE and added to headers. Let's create it when user logs
in, save in the cookie and reuse. It will have few infromation set in:

- browser
- system
- ip

This should be enough to determinate when user suddenly changed and log him out. We will be also adding salted app
secret for csrf.

## Email validation

https://github.com/symfonycasts/verify-email-bundle

# SECRETS

We will be using symfony vault - https://symfony.com/doc/current/configuration/secrets.html

- can commit keys from config/secrets/dev/ on dev
- never commit private key - config/secrets/dev/dev.decrypt.private.php on PROD
    - run prod with `APP_RUNTIME_ENV=prod php bin/console secrets:generate-keys`
    - script to periodically change security keys by `secrets:generate-keys --rotate`
- To set new env: `php bin/console secrets:set [name]`

# Fixtures

To reset test DB and populate it new data use fixtures:

```bash
APP_ENV=test php bin/console doctrine:fixtures:load
```

or

```bash
make reset-test-env
```

# TESTS

We are using [codeception](https://codeception.com/docs/Introduction) (which is base
on [PHPUint](https://phpunit.readthedocs.io/en/9.5/index.html))

- Verify for BDD assertions (you will probably have to go to the entity class because codeception changed names of the
  functions - `vendor/codeception/verify/src/Codeception/Verify/Verifiers/VerifyAny.php`) - https://github.com/Codeception/Verify/blob/master/docs/supported_verifiers.md
- `php vendor/bin/codecept build` to generate methods after changing settings
- https://github.com/Codeception/symfony-module-tests
- create Api suit and replace url with your local url:

```yaml
actor: ApiTester
modules:
  enabled:
    - REST:
        url: http://api.boardmeister.local/ # <-- url
        depends: Symfony
        part: Json
    - Symfony:
        app_path: 'src'
        environment: 'test'
```
- to run tests as www-data to avoid permission issues: `sudo runuser -u www-data make before-push`
- [symfony module](https://codeception.com/docs/modules/Symfony)
- to create tests you must run:
    - For Unit: `php vendor/bin/codecept generate:test Unit Dir/TestNameWithoutTestAtEnd`
    - For Integration: `php vendor/bin/codecept generate:test Integration Dir/TestNameWithoutTestAtEnd`
    - For Api: `php vendor/bin/codecept generate:test Api Dir/TestNameWithoutTestAtEnd`
- To run tests: `php vendor/bin/codecept run`
- To run detailed tests: `php vendor/bin/codecept run --steps` or `php vendor/bin/codecept run --debug`
- To run with coverage: `DEBUG_MODE=coverage php vendor/bin/codecept run --coverage --coverage-xml --coverage-html`
- To run only:
    - Unit: `php vendor/bin/codecept run Unit`
    - Integration: `php vendor/bin/codecept run Integration`
    - Api: `php vendor/bin/codecept run Api`
    - Single test: `php vendor/bin/codecept run Integration SigninCest.php` or full
      path `php vendor/bin/codecept run tests/acceptance/SigninCest.php` (https://codeception.com/docs/GettingStarted#running-tests)
- Usefull knowledge:
    - `_before` is run before each test
    - `_after` is run after each tests (if there was no error)
    - `_failed` is run after each failed test
    - `_passed` is run after each passed test
    - `_inject` used for injecting services (https://codeception.com/docs/AdvancedUsage#dependency-injection)
    - To skip test you can use `#[Skip]` (https://codeception.com/docs/AdvancedUsage#skip-tests)
    - Similarly to controllers you can group tests and run groups (https://codeception.com/docs/AdvancedUsage#groups)
        - `use Codeception\Attribute\Group;`
        - `#[Group('admin')]`
        - `php vendor/bin/codecept run -g admin -g editor`
    - Instead of providers you can user Examples: (https://codeception.com/docs/AdvancedUsage#examples-attribute)
        - `#[Examples('/api', 200)]`
        - or if you need a function `#[DataProvider('pageProvider')]`
    - Generate JUnit XML output:
        - `php vendor/bin/codecept run --steps --xml --html`
    - Assertion:
        - $this->assertEquals()
        - $this->assertContains()
        - $this->assertFalse()
        - $this->assertTrue()
        - $this->assertNull()
        - $this->assertEmpty()
    - Exceptions:
        - $this->expectException(ValidationException::class);
        - $this->expectExceptionMessageMatches('#Nieprawidłowy typ marki#');

# PHP Mess Detector

To suppress warning use `@SuppressWarnings(PHPMD.[warning name])`

```bash
php vendor/bin/phpmd src text phpmd.xml
php vendor/bin/phpmd tests text phpmd.xml
```

# PHP Stan

Command:

```bash
php vendor/bin/phpstan analyse src tests
```

To ignore single error use:

- @phpstan-ignore-line
- @phpstan-ignore-next-line

To ignore a batch of errors considers reading https://phpstan.org/user-guide/ignoring-errors and
updating `./phpstan.neon`

Known errors:
```bash
Could not write file: /tmp/phpstan/resultCache.php (file_put_contents(/tmp/phpstan/resultCache.php): Failed to open stream: Permission denied)
```

If you have previously run analysis as not current user that cache directory might have wrong owner. Change the owner to current user, and it should be fine

# PHP Code Style Fixer

```bash
vendor/bin/php-cs-fixer fix --verbose
```

Config options - https://mlocati.github.io/php-cs-fixer-configurator/#version:3.13

# PHP Code Sniffer

To ignore:

- file: `// phpcs:ignoreFile`
- single error: `// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore`
- to disable and enable: `// phpcs:disable // phpcs:enable`
- single line: `// phpcs:ignore`
  more at https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage#ignoring-files-and-folders

# Psalm

To suppress warning `@psalm-suppress InvalidReturnType` more
at https://psalm.dev/docs/running_psalm/dealing_with_code_issues/

Known problems: Can't create cache dir:
```bash
Uncaught RuntimeException: PHP Error: mkdir(): Permission denied in /var/www/html/boardmeister_internal/vendor/vimeo/psalm/src/Psalm/Config.php:2210 for command with CLI args "vendor/bin/psalm --taint-analysis" in /var/www/html/boardmeister_internal/vendor/vimeo/psalm/src/Psalm/Internal/ErrorHandler.php:75
```
if you are running commands as a www-data then make sure that /var/www/.cache directory is created and www-data is an owner of it.

# PHP

Ubuntu - update-alternatives --config php

## BCMath

in php.ini change bcmath.scale to 2

# JWT

https://jwt.io/

## Header manager

https://web-token.spomky-labs.com/the-components/header-checker

## Claim manager

https://web-token.spomky-labs.com/the-components/claim-checker

## Key

- https://web-token.spomky-labs.com/advanced-topics-1/security-recommendations
- symetric algorith
- 256 bits symmetric keys and at lease 2048 bits RSA keys
- additional in key:
    - kid: A unique key ID
    - use: indicates the usage of the key. Either sig (signature/verification) or enc (encryption/decryption).
    - alg: the algorithm allowed to be used with this key.
- Key rotation - once a week
- Token payload
    - as small as possible
    - have:
        - jti - unique identifier
        - exp: expiration time
        - iat: issuance time
        - nbf: validity point in time.
        - iss (issuer)
        - aud (audience)
    - When using encrypted tokens, the claims iss and aud should be duplicated into the header

## Validate

1. Unserialize the token
2. For each signature/recipient (may be possible when using the Json General Serialization Mode):
    1. Check the complete header (protected and unprotected)
    2. Verify the signature (JWS) or decrypt the token (JWE)
    3. Check the claims in the payload (if any)

If an error occurred during this process, you should consider the token as invalid.

Header parameters have to be checked. You should at least check the alg (algorithm) and enc (only for JWE) parameters.
The crit (critical) header parameter is always checked.

## Nested Token

We wanna use nested tokens to signature and encrypt
token - https://web-token.spomky-labs.com/advanced-topics-1/nested-tokens
