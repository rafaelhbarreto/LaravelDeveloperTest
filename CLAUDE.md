# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Context

This is a Laravel 12.x developer test project designed to evaluate a candidate's ability to:
- Build a data import system from a public API
- Use Laravel jobs, commands, and scheduled tasks
- Write testable, maintainable code with proper separation of concerns
- Utilize agentic coding tools effectively

The application should pull data from a public API (e.g., Congress.gov API), store it in a relational database, and display it via a simple web interface.

## Development Environment

### Using Laravel Sail (Docker - Recommended)
```bash
# Start all services (PostgreSQL, Redis, Laravel app)
./vendor/bin/sail up -d

# Run artisan commands
./vendor/bin/sail artisan [command]

# Run tests
./vendor/bin/sail test

# Run queue worker
./vendor/bin/sail artisan queue:work

# Stop services
./vendor/bin/sail down
```

### Using Composer Scripts (Local Development)
```bash
# Initial setup: installs dependencies, generates key, runs migrations, builds assets
composer setup

# Start development environment (runs server, queue worker, Pail logs, and Vite concurrently)
composer dev

# Run tests
composer test
# OR
php artisan test

# Run specific test
php artisan test --filter=TestName

# Run tests in parallel (faster)
php artisan test --parallel
```

### Individual Commands
```bash
# Development server
php artisan serve

# Queue worker (required for jobs)
php artisan queue:work
# OR for development (listens for changes)
php artisan queue:listen --tries=1

# Watch logs
php artisan pail

# Build assets
npm run build

# Watch assets (development)
npm run dev

# Code formatting
./vendor/bin/pint

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Architecture & Key Conventions

### Database
- **Queue Connection**: `database` - Jobs are stored in the database, not Redis
- **Session Driver**: `database` - Sessions stored in database
- **Cache Store**: `database` - Cache stored in database
- **Default DB (Docker)**: PostgreSQL (via Sail)
- **Test DB**: SQLite in-memory (configured in phpunit.xml)

### Service Layer Pattern
The test expects separation between data handling logic and execution mechanisms:
- **Services** (app/Services/): Business logic and API interactions
- **Jobs** (app/Jobs/): Idempotent queue-able jobs that call services
- **Commands** (app/Console/Commands/): Artisan commands that call services with visible output
- Services should be testable independently of Laravel's execution mechanisms

### Job Requirements
- Must be **idempotent** (safe to run multiple times without duplicating data)
- Should delegate business logic to service classes
- Use database queue (`QUEUE_CONNECTION=database` in .env)

### Testing Structure
- **Unit tests**: `tests/Unit/` - Test services in isolation
- **Feature tests**: `tests/Feature/` - Test full workflows (commands, jobs, routes)
- Use SQLite in-memory database for fast tests (configured by default)
- Run with `php artisan test` or `composer test`

### Laravel 12 Specifics
- Uses streamlined directory structure (minimal middleware, simplified routing)
- Service providers registered in `bootstrap/providers.php`
- Schedule defined in `routes/console.php` using fluent API or dedicated Kernel file
- Uses Vite for asset bundling with Tailwind CSS 4.0

### Expected Code Structure
```
app/
├── Console/Commands/      # Artisan commands with output for manual testing
├── Http/Controllers/      # Web controllers to display data
├── Jobs/                  # Queue-able jobs (idempotent)
├── Models/                # Eloquent models
└── Services/              # Business logic and API client services

database/
├── migrations/            # Database schema
└── factories/             # Model factories for testing

tests/
├── Feature/               # Integration tests
└── Unit/                  # Unit tests for services

routes/
├── console.php            # Schedule configuration
└── web.php               # Web routes
```

## Important Files

- `composer.json`: Contains helpful scripts like `composer dev` and `composer test`
- `phpunit.xml`: Test configuration (uses SQLite in-memory)
- `compose.yaml`: Laravel Sail Docker configuration (PostgreSQL, Redis)
- `.env.example`: Environment template with database queue configuration

## Test Deliverables Checklist

When implementing the test, ensure:
1. Models and migrations for storing API data
2. Service classes with clean, testable API interaction logic
3. Idempotent Laravel jobs for data fetching/processing
4. Artisan command for manual data import with visible output
5. Scheduled task configuration in `routes/console.php`
6. Routes and controllers for web interface
7. Unit/feature tests for services
8. Updated README with setup instructions
9. `AGENTIC_CODING_NOTES.md` documenting agent usage

## Common Patterns

### Creating an Idempotent Job
```php
// Use updateOrCreate, upsert, or firstOrCreate to avoid duplicates
$model->updateOrCreate(
    ['external_id' => $data['id']], // Unique identifier
    ['name' => $data['name'], ...] // Data to update
);
```

### Scheduled Tasks (routes/console.php)
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('your:command')->daily();
// OR
Schedule::job(new YourJob)->hourly();
```

### Testing Services
```php
// Mock external HTTP calls
Http::fake([
    'api.example.com/*' => Http::response(['data' => [...]], 200)
]);

// Test service in isolation
$service = new YourService();
$result = $service->fetchData();
```
