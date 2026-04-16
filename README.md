# FluxPay

Fourth-party payment aggregation system supporting collection (deposit) and disbursement (withdrawal) with a 3-level agent hierarchy and multi-provider gateway integration.

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Admin Panel**: Orchid Platform 14
- **Database**: MariaDB 10.6+
- **Cache / Queue**: Redis 7+
- **Queue Monitor**: Laravel Horizon

## Requirements

- PHP 8.2+ with extensions: `mbstring`, `xml`, `ctype`, `json`, `bcmath`, `pdo_mysql`, `redis`
- MariaDB 10.6+
- Redis 7+
- Composer 2.x

## Installation

### 1. Clone & install dependencies

```bash
git clone git@gitlab.com:topiastsui/FluxPay.git fluxpay
cd fluxpay
php8.2 $(which composer) install
```

### 2. Environment configuration

```bash
cp .env.example .env
php8.2 artisan key:generate
```

Edit `.env` with your database and Redis credentials:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fluxpay
DB_USERNAME=root
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Create database

```sql
CREATE DATABASE fluxpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run migrations

```bash
php8.2 artisan migrate
```

### 5. Create admin user

```bash
php8.2 artisan orchid:admin admin admin@fluxpay.com your_password
```

### 6. Publish assets

```bash
php8.2 artisan vendor:publish --tag=laravel-assets --force
```

### 7. Start services

```bash
# Development server
php8.2 artisan serve

# Queue worker (separate terminal)
php8.2 artisan horizon
```

### 8. Access

- **Admin Panel**: `http://localhost:8000/admin`
- **Horizon Dashboard**: `http://localhost:8000/horizon`

## CLI Convention

Always use `php8.2` instead of bare `php`:

```bash
php8.2 artisan migrate
php8.2 artisan tinker
php8.2 artisan test
php8.2 vendor/bin/phpunit
php8.2 $(which composer) install
```

## Architecture

```
Controller -> Service -> Repository -> Model
```

See `docs/FluxPay.md` for the full architecture plan.

## Testing

```bash
php8.2 artisan test
```

## License

Proprietary. All rights reserved.
