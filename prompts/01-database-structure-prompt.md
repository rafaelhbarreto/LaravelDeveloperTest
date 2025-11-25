# Prompt 1 – Database Application Structure (Laravel 12 + Congress.gov API)

## Context

I am building a Laravel 12 application that consumes data from the public Congress.gov API (U.S. Congress Members API).
I need to design the database schema to store information about congressional members and their terms.

## Business Requirements

### 1. Store data about members of Congress

-   Bioguide ID (unique identifier from the API)
-   Full name
-   Political party
-   State represented
-   District (if applicable)
-   Official photo URL
-   Photo attribution
-   Official profile URL
-   Last updated date (from the API)

### 2. Store historical terms

-   Members may have multiple terms
-   Chamber: House of Representatives or Senate
-   Start year
-   End year (nullable if the term is active)

### 3. Relationships

-   A Member has many terms
-   A Term belongs to a Member
-   Deleting a member should cascade delete all its terms

## Technical Requirements

### Technology Stack

-   Framework: Laravel 12.x
-   PHP: 8.4+
-   Database: PostgreSQL (production/development), SQLite (tests)
-   ORM: Eloquent
-   Standards: PSR-12, strict typing enabled

## Expected Structure

### 1. Migrations

-   Use standard Laravel naming conventions
-   Soft deletes for `members`
-   Foreign keys with cascade delete
-   Indexes for performance:
    -   `bioguide_id` (unique)
    -   `member_id` in `member_terms`

### 2. Models

-   Use PHP 8.4 constructor property promotion
-   Define `$fillable` arrays
-   Configure `$casts` (int, datetime, etc.)
-   Implement relationships with type hints
-   Use `SoftDeletes` in the Member model

### 3. Factories

-   Create factories for Member and MemberTerm
-   Use Faker to generate realistic test data
-   Allow creation of Member with Terms
-   Support custom factory states (`withTerms`, `current`, `expired`)

## Tasks

### 1. Migration: Members Table

```bash
php artisan make:migration create_members_table
```

Fields:

-   `id`
-   `bioguide_id` (string, unique)
-   `name` (string, max 100)
-   `party_name` (string, nullable)
-   `state` (string, nullable)
-   `district` (unsigned integer, nullable)
-   `depiction_image_url` (string, nullable)
-   `depiction_attribution` (string, nullable)
-   `url` (string, nullable)
-   `updated_date` (timestamp)
-   `timestamps`
-   `softDeletes`

### 2. Migration: MemberTerms Table

```bash
php artisan make:migration create_member_terms_table
```

Fields:

-   `id`
-   `member_id` (FK → members.id, cascade delete)
-   `chamber` (string, nullable)
-   `start_year` (integer)
-   `end_year` (integer, nullable)
-   `timestamps`

### 3. Migration: Indexes

```bash
php artisan make:migration add_indexes_to_congress_tables
```

Composite indexes:

-   (`member_id`, `chamber`, `start_year`)
-   (`party_name`, `state`)

### 4. Model: Member

```bash
php artisan make:model Member
```

Implement:

-   Traits: `HasFactory`, `SoftDeletes`
-   `$fillable` with all attributes
-   `$casts` (district → int, updated_date → datetime)
-   Relationship:

```php
public function terms(): HasMany
```

### 5. Model: MemberTerm

```bash
php artisan make:model MemberTerm
```

Implement:

-   Trait: `HasFactory`
-   `$fillable`
-   `$casts` (years → integers)
-   Relationship:

```php
public function member(): BelongsTo
```

### 6. Factory: Member

```bash
php artisan make:factory MemberFactory
```

Use Faker to generate:

-   `bioguide_id` (pattern `[A-Z]\d{6}`)
-   Full name
-   Party name
-   State abbreviation
-   District number
-   Realistic image URLs

### 7. Factory: MemberTerm

```bash
php artisan make:factory MemberTermFactory
```

Use Faker to generate:

-   Chamber
-   Start year (2000 → current year)
-   End year (null or > start_year)

## Expected Validations

### Referential integrity

-   Terms must always belong to a valid Member
-   Deleting a Member must delete its Terms

### Uniqueness

-   `bioguide_id` must be unique

### Data type consistency

-   All years must be integers
-   District must be a positive integer
-   Dates must be valid timestamps

## Expected Output

After running:

```bash
php artisan migrate
```

The system should create:

-   A `members` table (with ~11 columns + timestamps + soft deletes)
-   A `member_terms` table
-   Proper indexes for performance

## Important Notes

-   Follow Laravel 12 conventions
-   Always use `declare(strict_types=1)`
-   Naming:
    -   DB: `snake_case`
    -   PHP: `camelCase`
-   Code formatting: Laravel Pint
-   Add PHPDoc only where needed

## References

-   Laravel 12 Migrations
-   Eloquent Relationships
-   PostgreSQL Data Types
-   Congress.gov API conventions
