# CLAUDE.md

## Project

FluxPay - Fourth-party payment aggregation system (deposit & withdrawal).

## Tech Stack

- Laravel 11, PHP 8.2+, Orchid Platform 14, MariaDB, Redis
- Queue: Redis + Laravel Horizon

## CLI

Always use `php8.2`, never bare `php`:

```
php8.2 artisan migrate
php8.2 artisan test
php8.2 vendor/bin/phpunit
php8.2 $(which composer) install
```

## Architecture

```
Controller -> Service -> Repository -> Model
```

- **Controller**: Receive request, FormRequest validation, call Service, return Resource. No business logic.
- **Service**: Business logic, orchestration, DB::transaction, event dispatch. No direct queries.
- **Repository**: Data access, query composition, lockForUpdate, batch ops. No business flow.
- **Model**: ORM mapping, relations, accessor/mutator, scope. No business logic.

## API Response Format

```json
{"code": 0, "message": "Success", "data": {}, "timestamp": "2026-04-16 12:00:00"}
```

## Conventions

- Status values use PHP Enums, stored as strings/integers in DB
- Money/amounts use integers or DECIMAL(20,6), never float
- Time uses Carbon
- Queries must specify columns, avoid `select *`
- Avoid magic strings
- Commit messages in English
- Do not auto `git push` unless explicitly asked

## Work Rules

Before making changes involving any of these, output the full plan and wait for confirmation:

- New or modified DB tables (migration)
- New or modified API endpoints / response structures
- Changes across multiple files

Small single-file fixes can be done directly.

## Response Style

- Reply in Chinese by default, code/variable/class/function names in English
- Conclusion first, then reasoning
- For multi-file changes, list files with what changed
- Point out problems directly

## Key Paths

- Admin panel routes: `routes/platform.php`
- API routes: `routes/api.php`
- Orchid screens: `app/Orchid/Screens/`
- Services: `app/Services/`
- Repositories: `app/Repositories/`
- Models: `app/Models/`
- Migrations: `database/migrations/`
- Architecture plan: `docs/FluxPay.md`

## Testing

```
php8.2 artisan test
php8.2 vendor/bin/phpunit
```
