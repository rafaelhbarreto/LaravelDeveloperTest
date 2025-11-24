# Prompt 2 – API Integration and Data Storage (Laravel 12 + Congress.gov API)

## Context

After structuring the database, I need to implement the integration layer with the Congress.gov API to fetch congressional member data and store it in the database. The solution must follow Clean Architecture with clear separation of responsibilities.

## Congress.gov API

### Main Endpoint

```
GET https://api.congress.gov/v3/member
```

### Authentication

-   Requires an API key sent via the `api_key` query parameter or the `x-api-key` header.
-   API key available at: https://api.congress.gov/sign-up/

### API Response (Relevant Structure)

```json
{
    "members": [
        {
            "bioguideId": "D000001",
            "name": "John Doe",
            "partyName": "Democratic",
            "state": "CA",
            "district": 12,
            "depiction": {
                "imageUrl": "https://...",
                "attribution": "Official photo"
            },
            "url": "https://...",
            "updateDate": "2024-01-15",
            "terms": [
                {
                    "chamber": "House of Representatives",
                    "startYear": 2020,
                    "endYear": null
                }
            ]
        }
    ],
    "pagination": {
        "count": 250,
        "next": "https://api.congress.gov/v3/member?offset=250"
    }
}
```

### Pagination

-   The API returns **250 records per page**
-   The `pagination.next` field contains the URL for the next page
-   If there is no next page, the field is `null` or missing

---

## Required Architecture

### Clean Architecture Layers

```
Presentation Layer (Commands)
    ↓
Application Layer (UseCases)
    ↓
Domain Layer (Services)
    ↓
Infrastructure Layer (Repositories)
    ↓
Database (Eloquent Models)
```

### File Structure

```
app/
├── Console/Commands/
│   └── FetchCongressMembersCommand.php
├── Jobs/
│   ├── FetchCongressMembersPageJob.php
│   └── StoreMemberDataJob.php
├── UseCases/
│   └── FetchAndStoreCongressMembersUseCase.php
├── Services/
│   └── CongressApiClient.php
├── Repositories/
│   └── MemberRepository.php
├── Contracts/
│   ├── CongressApiClientInterface.php
│   └── MemberRepositoryInterface.php
├── DTO/
│   ├── MemberData.php
│   ├── MemberTermData.php
│   └── PaginationData.php
├── Exceptions/
│   └── CongressApiException.php
└── Providers/
    └── AppServiceProvider.php (update bindings)

config/
└── congress.php

routes/
└── console.php (schedule task)
```

---

## Functional Requirements

### 1. Data Transfer Objects (DTOs)

Use **Spatie Laravel Data** to create immutable, type-safe DTOs.

---

### 2. Service – CongressApiClient

**Responsibilities:**

-   Perform HTTP requests to the API
-   Convert JSON responses into DTOs
-   Handle API errors (401, 404, 500)
-   Implement retry logic (3 attempts)
-   Log all requests

**Implementation:**

-   Use `Illuminate\Support\Facades.Http`
-   Headers:
    -   `x-api-key`
    -   `Accept: application/json`
-   Timeout: **30 seconds**
-   Retry: **3 attempts**, **1-second backoff**
-   Throw `CongressApiException` on failure

---

### 3. Repository – MemberRepository

**Responsibilities:**

-   Handle database persistence
-   Implement idempotent update logic
-   Manage member ↔ term relationship

**Upsert Logic:**

1. Find member by `bioguide_id`
2. If none exists → create new
3. If exists → compare `updated_date`:
    - If API > DB → update
    - If DB >= API → skip update
4. Update terms:
    - Delete all old terms
    - Insert new terms
5. Return updated member with terms loaded

---

### 4. UseCase – FetchAndStoreCongressMembersUseCase

**Responsibility:**  
Coordinate the entire fetch + store workflow.

---

### 5. Jobs

---

#### FetchCongressMembersPageJob

**Responsibilities:**

-   Fetch a page of members
-   Dispatch jobs to store each member
-   Dispatch next fetch job if more data exists
-   Respect the global limit (if provided)

**Queue:** `congress-fetch`  
**Tries:** 3  
**Timeout:** 120 seconds  
**Backoff:** `[10, 30, 60]` seconds

**Flow:**

1. Calculate records remaining (considering limit)
2. Fetch page with CongressApiClient
3. Dispatch `StoreMemberDataJob` for each member
4. If next page exists AND limit not reached → dispatch next fetch job
5. Log progress

---

#### StoreMemberDataJob

**Responsibility:**  
Persist a single member in the database.

**Queue:** `congress-store`  
**Tries:** 3  
**Timeout:** 30 seconds

**Flow:**

1. Call `MemberRepository::upsertMember()`
2. Log success or failure

---

### 6. Command – FetchCongressMembersCommand

**Responsibility:**  
Provide CLI entry point for fetching data.

**Options:**

-   `--limit`: Maximum number of members to fetch

---

### 7. Scheduled Task

Defined in:

```
routes/console.php
```

---

### 8. Configuration

**File:** `config/congress.php`

```php
return [
    'api_key' => env('CONGRESS_API_KEY'),
    'base_url' => env('CONGRESS_API_BASE_URL', 'https://api.congress.gov/v3'),
    'chunk_size' => env('CONGRESS_API_CHUNK_SIZE', 250),
    'timeout' => env('CONGRESS_API_TIMEOUT', 30),
    'retry_times' => env('CONGRESS_API_RETRY_TIMES', 3),
    'fetch_queue' => env('CONGRESS_FETCH_QUEUE', 'congress-fetch'),
    'store_queue' => env('CONGRESS_STORE_QUEUE', 'congress-store'),
];
```

**.env**

```env
CONGRESS_API_KEY=your_api_key_here
QUEUE_CONNECTION=database
```

---

## Non-Functional Requirements

1. **Idempotency** – Running the command multiple times must not duplicate data
2. **Performance** – Process 250 members per page using parallel jobs
3. **Resilience** – Retry on API or DB failures
4. **Logging** – Log every important operation
5. **Type Safety** – Strict types everywhere
6. **Testability** – All components testable via mocks
7. **SOLID** – Interfaces + dependency injection everywhere

### Graceful Degradation

-   If a member record is invalid → log → skip
-   If a page fails → retry via job mechanism
-   If API is down → queue retries later

---

## Complete Workflow

```
1. User runs: php artisan congress:fetch-members --limit=500
    ↓
2. FetchCongressMembersCommand validates input
    ↓
3. FetchAndStoreCongressMembersUseCase.execute(500)
    ↓
4. Dispatch FetchCongressMembersPageJob(limit: 500, offset: 0)
    ↓
5. Job fetches page #1 (250 members)
    ↓
6. Dispatch 250 StoreMemberDataJob (parallel)
    ↓
7. Dispatch next FetchCongressMembersPageJob (offset: 250)
    ↓
8. Fetch second page (250 members)
    ↓
9. Dispatch 250 StoreMemberDataJob
    ↓
10. Limit reached (500) → STOP
```

---

## Final Validations

1. Command runs without errors
2. Jobs processed (check `jobs`/`failed_jobs` tables)
3. Members stored correctly
4. Running command again does **not** duplicate rows
5. Logs available for debugging

---

## Notes

-   **Spatie Laravel Data** provides immutable DTOs
-   **Separate jobs** → parallel processing + independent retries
-   **Repository pattern** → persistence abstraction
-   **Interfaces** → easier mocking for tests
-   **Clean Architecture** → separation of concerns preserved
