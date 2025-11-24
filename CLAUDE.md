# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Laravel 12 Congress Members Application** - Full-stack data import and display system:

-   Fetches data from Congress.gov API (https://api.congress.gov/v3)
-   Stores member and term data in PostgreSQL database
-   Processes data asynchronously using queue jobs
-   Displays data through traditional Blade views
-   Follows Clean Architecture principles

**Status**: ✅ Production Ready - All core features implemented and tested

## Tech Stack

### Backend

-   **Framework**: Laravel 12.x (PHP 8.4)
-   **Database**: PostgreSQL (production) / SQLite (testing)
-   **Queue**: Database-driven (separate queues: `congress-fetch`, `congress-store`)
-   **Testing**: Pest 3.x (43 tests, 119 assertions)
-   **DTOs**: Spatie Laravel Data

### Frontend

-   **Views**: Blade templates
-   **Styling**: TailwindCSS 4.0
-   **Build**: Vite 7.0

## Architecture (Clean Architecture Pattern)

### Layer 1: Presentation (Controllers, Commands)

-   **Location**: `app/Http/Controllers/`, `app/Console/Commands/`
-   **Rule**: THIN - No business logic, delegate to UseCases
-   **Example**: `CongressMemberController`, `FetchCongressMembersCommand`

### Layer 2: Application (UseCases)

-   **Location**: `app/UseCases/`
-   **Rule**: Orchestrate workflows, NO direct DB/API calls
-   **Example**: `FetchAndStoreCongressMembersUseCase`, `GetCongressMembersListUseCase`

### Layer 3: Domain (Models, DTOs, Contracts)

-   **Location**: `app/Models/`, `app/DTO/`, `app/Contracts/`
-   **Models**: `Member` (soft deletes), `MemberTerm`
-   **DTOs**: `MemberData`, `MemberTermData`, `MemberListFiltersData`, `PaginationData`
-   **Contracts**: `CongressApiClientInterface`, `MemberRepositoryInterface`

### Layer 4: Infrastructure (Services, Repositories, Jobs)

-   **Location**: `app/Services/`, `app/Repositories/`, `app/Jobs/`
-   **Services**: `CongressApiClient` (HTTP client with retry logic)
-   **Repositories**: `MemberRepository` (idempotent upsert)
-   **Jobs**: `FetchCongressMembersPageJob`, `StoreMemberDataJob`

## Directory Structure

```
app/
├── Console/Commands/          # CLI commands
├── Contracts/                 # Interfaces
├── DTO/                       # Data Transfer Objects (Spatie Laravel Data)
├── Exceptions/                # Custom exceptions
├── Http/
│   ├── Controllers/           # HTTP controllers
│   ├── Middleware/            # Custom middleware
│   └── Resources/             # API Resources
├── Jobs/                      # Queue jobs (idempotent)
├── Models/                    # Eloquent models
├── Providers/                 # Service providers (DI bindings)
├── Repositories/              # Data access layer
├── Services/                  # External integrations
└── UseCases/                  # Business workflows

config/
└── congress.php               # Congress API configuration

resources/
├── css/
│   └── app.css                # Tailwind CSS
└── views/
    ├── congress/
    │   └── members/           # Member views (index, show)
    ├── layouts/
    │   └── app.blade.php      # Main layout
    └── welcome.blade.php

routes/
├── console.php                # Scheduled tasks
└── web.php                    # HTTP routes

tests/
├── Feature/Controllers/       # Integration tests
└── Unit/                      # Unit tests (DTO, Services, UseCases, Repositories)
```

## Critical Code Standards

### 1. Every PHP File MUST Start With

```php
<?php

declare(strict_types=1);

namespace App\...;
```

### 2. Constructor Property Promotion (REQUIRED)

```php
public function __construct(
    private readonly CongressApiClientInterface $apiClient,
    private readonly MemberRepositoryInterface $repository
) {}
```

### 3. Type Declarations (REQUIRED)

```php
public function execute(?int $limit = null): void
private function buildFilters(Request $request): MemberListFiltersData
```

### 4. Readonly Properties for DTOs

```php
class MemberData extends Data
{
    public function __construct(
        public readonly string $bioguideId,
        public readonly string $name,
        // ...
    ) {}
}
```

### 5. Small Methods (3-7 lines, max 15)

Extract complex logic into private methods with self-documenting names.

### 6. Dependency Inversion

```php
// ✅ GOOD - Depend on interface
public function __construct(
    private readonly GetCongressMembersListUseCase $useCase
) {}

// ❌ BAD - Depend on concrete class
public function __construct(
    private readonly MemberRepository $repository
) {}
```

### 7. No Comments - Self-Documenting Code

```php
// ✅ GOOD
if ($this->shouldFetchNextPage($pagination, $newTotalProcessed)) {
    $this->dispatchNextFetchJob($newTotalProcessed);
}

// ❌ BAD - Needs comment
// Check if we should fetch the next page
if ($pagination->hasNextPage() && ...) {
```

## Key Conventions

### Job Requirements

-   **MUST** be idempotent (safe to retry)
-   **MUST** specify queue name via config
-   **MUST** have `public int $tries = 3`
-   **MUST** implement `failed()` method
-   **MUST** use database queue

### Repository/Service Pattern

-   Services: External API calls, business logic
-   Repositories: Database operations ONLY
-   Both MUST implement interfaces and be bound in `AppServiceProvider`

### Eloquent Best Practices

-   Always use relationships with type hints
-   Eager load to prevent N+1 queries
-   Use transactions for complex operations
-   Proper indexes on all foreign keys and query columns

### Views & Blade

-   Use Blade components for reusable UI elements
-   Pass data through controllers using `view()` helper
-   Use `@extends` and `@section` for layouts
-   Keep views simple - logic belongs in controllers/UseCases

### Testing

-   Mock external dependencies (HTTP, APIs)
-   Use factories for model creation
-   Zero external API calls in tests
-   Unit tests: Isolated (no DB/HTTP)
-   Feature tests: Integration (test DB, mocked APIs)

## Development Workflow

### Quick Start

```bash
# Setup
composer setup

# Development (server + queues + logs)
composer dev

# Tests
composer test
```

### Queue Workers (REQUIRED for data processing)

```bash
php artisan queue:work --queue=congress-fetch
php artisan queue:work --queue=congress-store
```

### Data Import

```bash
# Fetch all members
php artisan congress:fetch-members

# Fetch with limit
php artisan congress:fetch-members --limit=500
```

### Scheduled Task

-   **Command**: `congress:fetch-members`
-   **Frequency**: Daily at 00:00 UTC
-   **Location**: `routes/console.php`
-   **Features**: `withoutOverlapping()`, `onOneServer()`, `runInBackground()`

## Data Flow

### Import Workflow

```
Command → UseCase → FetchJob (congress-fetch queue)
  → CongressApiClient (HTTP)
  → Parse to DTOs
  → Dispatch 250x StoreJob (congress-store queue)
  → MemberRepository (idempotent upsert)
  → Database
```

### Smart Idempotency

-   Only updates if API `updated_date` > local `updated_date`
-   Skips unnecessary writes
-   Gracefully handles invalid data
-   Comprehensive logging

### Web Interface

```
Route → Controller → UseCase → Repository
  → Eloquent with eager loading
  → MemberResource transformation
  → view() with data
  → Blade template rendering
```

## Configuration

### Required Environment Variables

```
CONGRESS_API_KEY=your_key_here
CONGRESS_API_BASE_URL=https://api.congress.gov/v3
CONGRESS_FETCH_CHUNK_SIZE=250
CONGRESS_FETCH_QUEUE=congress-fetch
CONGRESS_STORE_QUEUE=congress-store
```

### Important Files

-   `config/congress.php` - API and queue configuration
-   `composer.json` - Scripts: setup, dev, test
-   `phpunit.xml` - Test configuration (SQLite in-memory)
-   `tests/Pest.php` - Global test helpers

## Routes

### Web Routes

-   `GET /congress/members` - List with filters, sorting, pagination
-   `GET /congress/members/{bioguideId}` - Member details

### Filters

-   `name` - Case-insensitive search
-   `party` - Exact match
-   `state` - Exact match
-   `sort_by` - Whitelisted columns: name, party_name, state, district, updated_date
-   `sort_direction` - asc/desc
-   `per_page` - Results per page (default: 25)

## Testing Status

### ✅ Passing Tests (43 tests, 119 assertions)

-   DTOs: 16 tests (100% coverage)
-   Services: 15 tests (HTTP mocked)
-   UseCases: 12 tests (dependencies mocked)
-   Controllers: 21+ tests (end-to-end)

### ⏸️ Pending

-   Repository tests (written, pending DB config)
-   Job integration tests
-   End-to-end workflow tests

## Common Patterns

### Exception Factory Methods

```php
throw CongressApiException::missingApiKey();
throw CongressApiException::requestFailed($status, $url);
```

### Resource Transformation

```php
return MemberResource::collection($members);
```

### View Rendering

```php
return view('congress.members.index', [
    'members' => $data,
    'filters' => $filters,
]);
```

### Configuration Access

```php
// ✅ GOOD
$chunkSize = config('congress.api.chunk_size');

// ❌ BAD - Never use env() outside config files
$apiKey = env('CONGRESS_API_KEY');
```

## Documentation Files

-   `CLAUDE.md` - This file (project guidelines)
-   `README.md` - Basic setup instructions
-   `PEST_TEST_SUMMARY.md` - Testing strategy
-   `PEST_DATABASE_SETUP.md` - DB config guide
-   `RESTART_QUEUE.md` - Queue troubleshooting
-   `SCHEDULER_SETUP.md` - Cron setup

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

-   php - 8.4.14
-   laravel/framework (LARAVEL) - v12
-   laravel/prompts (PROMPTS) - v0
-   laravel/mcp (MCP) - v0
-   laravel/pint (PINT) - v1
-   laravel/sail (SAIL) - v1
-   pestphp/pest (PEST) - v3
-   phpunit/phpunit (PHPUNIT) - v11
-   tailwindcss (TAILWINDCSS) - v4

## Conventions

-   You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
-   Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
-   Check for existing components to reuse before writing a new one.

## Verification Scripts

-   Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture

-   Stick to existing directory structure - don't create new base folders without approval.
-   Do not change the application's dependencies without approval.

## Frontend Bundling

-   If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build` or `npm run dev`. Ask them.

## Replies

-   Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files

-   You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost

-   Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

-   Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs

-   Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging

-   You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
-   Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

-   You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
-   Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

-   Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
-   The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
-   You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
-   Search the documentation before making code changes to ensure we are taking the correct approach.
-   Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
-   Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

-   You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms

=== php rules ===

## PHP

-   Always use curly braces for control structures, even if it has one line.

### Constructors

-   Use PHP 8 constructor property promotion in `__construct()`.
    -   <code-snippet>public function \_\_construct(public GitHub $github) { }</code-snippet>
-   Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations

-   Always use explicit return type declarations for methods and functions.
-   Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments

-   Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks

-   Add useful array shape type definitions for arrays when appropriate.

## Enums

-   Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== laravel/core rules ===

## Do Things the Laravel Way

-   Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
-   If you're creating a generic PHP class, use `artisan make:class`.
-   Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database

-   Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
-   Use Eloquent models and relationships before suggesting raw database queries
-   Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
-   Generate code that prevents N+1 query problems by using eager loading.
-   Use Laravel's query builder for very complex database operations.

### Model Creation

-   When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

-   For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation

-   Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
-   Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues

-   Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization

-   Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation

-   When generating links to other pages, prefer named routes and the `route()` function.

### Configuration

-   Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing

-   When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
-   Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
-   When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error

-   If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or `npm run dev`.

=== laravel/v12 rules ===

## Laravel 12

-   Use the `search-docs` tool to get version specific documentation.
-   Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure

-   No middleware files in `app/Http/Middleware/`.
-   `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
-   `bootstrap/providers.php` contains application specific service providers.
-   **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
-   **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database

-   When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
-   Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

-   Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

## Laravel Pint Code Formatter

-   You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
-   Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

### Testing

-   If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests

-   All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
-   You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
-   Tests should test all of the happy paths, failure paths, and weird paths.
-   Tests live in the `tests/Feature` and `tests/Unit` directories.
-   Pest tests look and behave like this:
    <code-snippet name="Basic Pest Test Example" lang="php">
    it('is true', function () {
    expect(true)->toBeTrue();
    });
    </code-snippet>

### Running Tests

-   Run the minimal number of tests using an appropriate filter before finalizing code edits.
-   To run all tests: `php artisan test`.
-   To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
-   To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
-   When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions

-   When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
    <code-snippet name="Pest Example Asserting postJson Response" lang="php">
    it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

        $response->assertSuccessful();

    });
    </code-snippet>

### Mocking

-   Mocking can be very helpful when appropriate.
-   When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
-   You can also create partial mocks using the same import or self method.

### Datasets

-   Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== tailwindcss/core rules ===

## Tailwind Core

-   Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
-   Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade components).
-   Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
-   You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing

-   When listing items, use gap utilities for spacing, don't use margins.

      <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
          <div class="flex gap-8">
              <div>Superior</div>
              <div>Michigan</div>
              <div>Erie</div>
          </div>
      </code-snippet>

### Dark Mode

-   If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind 4

-   Always use Tailwind CSS v4 - do not use the deprecated utilities.
-   `corePlugins` is not supported in Tailwind v4.
-   In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities

-   Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
-   Opacity values are still numeric.

| Deprecated             | Replacement          |
| ---------------------- | -------------------- |
| bg-opacity-\*          | bg-black/\*          |
| text-opacity-\*        | text-black/\*        |
| border-opacity-\*      | border-black/\*      |
| divide-opacity-\*      | divide-black/\*      |
| ring-opacity-\*        | ring-black/\*        |
| placeholder-opacity-\* | placeholder-black/\* |
| flex-shrink-\*         | shrink-\*            |
| flex-grow-\*           | grow-\*              |
| overflow-ellipsis      | text-ellipsis        |
| decoration-slice       | box-decoration-slice |
| decoration-clone       | box-decoration-clone |

=== tests rules ===

## Test Enforcement

-   Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
-   Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
    </laravel-boost-guidelines>
