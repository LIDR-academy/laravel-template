<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Development environment (Docker)

The project runs entirely in Docker. All you need on your machine is
**Docker**, **Docker Compose** and **GNU Make**.

### ⚡ Quick start

```bash
make setup
```

That single command handles the whole initial bootstrap:

1. Creates `.env` from `.env.example` (if it doesn't exist).
2. Builds the application image.
3. Starts all containers and waits for them to be healthy.
4. Installs Composer dependencies.
5. Generates the `APP_KEY`.
6. Runs the migrations.

Once it finishes, the services are available at:

| Service         | URL                     |
|-----------------|-------------------------|
| Application     | http://localhost:8000   |
| Vite (HMR)      | http://localhost:5173   |
| Mailpit (mail)  | http://localhost:8025   |
| pgAdmin (DB)    | http://localhost:5050   |

> **Stack services:** `app` (PHP 8.4 php-fpm + Composer, with `pdo_pgsql` and
> `redis`), `nginx`, `db` (PostgreSQL 16 — dev DB `laravel` and a separate test
> DB `laravel_test`), `redis` (cache and queues), `vite` (Node 24 with hot
> reload), `mailpit` (mail catcher) and `pgadmin` (DB manager).

> **Port conflict?** If a default port is already in use, override it when
> starting, e.g. `APP_PORT=8001 DB_PORT_HOST=5433 make up`. Available variables:
> `APP_PORT`, `DB_PORT_HOST`, `REDIS_PORT_HOST`, `VITE_PORT`, `MAILPIT_UI_PORT`,
> `MAILPIT_SMTP_PORT`, `PGADMIN_PORT`.

### 🛠️ Make commands

Run `make` or `make help` to see the full list. Summary:

#### Bootstrap
| Command      | Description                                                |
|--------------|------------------------------------------------------------|
| `make setup` | Full initial setup (env, build, up, deps, key, migrate)    |

#### Container lifecycle
| Command        | Description                                          |
|----------------|------------------------------------------------------|
| `make up`      | Start all containers in the background               |
| `make down`    | Stop and remove the containers                       |
| `make restart` | Restart all containers                               |
| `make stop`    | Stop the containers without removing them            |
| `make build`   | Build the application image                          |
| `make rebuild` | Rebuild the image from scratch (no cache)            |
| `make ps`      | Show running containers                              |
| `make logs`    | Tail logs in real time (CTRL+C to exit)             |
| `make clean`   | Stop containers and **DELETE** the volumes (data)    |

#### Database
| Command         | Description                                  |
|-----------------|----------------------------------------------|
| `make migrate`  | Run the migrations                           |
| `make rollback` | Roll back the last migration batch           |
| `make fresh`    | Drop all tables and re-migrate + seeders     |
| `make seed`     | Run the seeders                              |
| `make psql`     | Open a `psql` shell on the dev database      |

#### Development
| Command                            | Description                                     |
|------------------------------------|-------------------------------------------------|
| `make shell`                       | Open a bash shell inside the `app` container    |
| `make tinker`                      | Open Laravel Tinker                             |
| `make artisan ARGS="route:list"`   | Run an artisan command                          |
| `make composer ARGS="require ..."` | Run a composer command                          |
| `make npm ARGS="install ..."`      | Run an npm command in the `vite` container      |
| `make fmt`                         | Format the code with Laravel Pint               |

#### Tests
> They use the `laravel_test` DB, separate from the dev DB — running tests never touches your data.

| Command                             | Description                          |
|-------------------------------------|--------------------------------------|
| `make test`                         | Run the test suite (Pest)            |
| `make test-filter ARGS="UserTest"`  | Run tests matching the filter        |

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
