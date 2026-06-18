# 01 — Enable the coverage driver (one-off, infra)

```
`make test-coverage` (php artisan test --coverage) fails because the Docker PHP image has
NO code coverage driver. This is NOT a Pest-vs-PHPUnit issue — Pest runs on PHPUnit and
both need a driver (PCOV or Xdebug).

Fix it by adding PCOV (fast, coverage-only) to docker/php/Dockerfile:
- Inside the existing extension RUN block, BEFORE the `apk del $PHPIZE_DEPS` cleanup line
  (PHPIZE_DEPS must still be present to build it), add:
      && pecl install pcov \
      && docker-php-ext-enable pcov \
- Rebuild the PHP image and restart the stack using the project's make/docker compose targets.
- Verify inside the container: `php -m | grep -i pcov` lists pcov, and
  `php artisan test --coverage` now prints a coverage report.
- Update the Makefile `test-coverage` target to enforce the floor:
      php artisan test --coverage --min=80

IMPORTANT: after the driver works, coverage WILL be low and --min=80 WILL fail — the
seeded tests only cover POST endpoints. That low number is the intended teaching point;
do NOT add tests to make it pass. Just report the coverage % and confirm pcov is loaded.
```
