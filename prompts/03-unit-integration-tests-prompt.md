# Prompt 3 – Testing Strategy (Unit & Integration Tests with Pest PHP)

## Context

After implementing API integration and data storage, I need to create a comprehensive test suite using **Pest PHP**.  
The tests must cover every layer of the application (DTOs, Services, Repositories, UseCases, Jobs, Commands), without relying on any real external dependencies.

---

## Testing Framework

### **Pest PHP 3.x**

**Why Pest?**

-   Clean, expressive syntax
-   Native Laravel integration
-   Built‑in mocks, fakes, and assertions
-   Faster and cleaner than PHPUnit

---

## Test Structure

```
tests/
├── Feature/
│   ├── Console/
│   │   └── FetchCongressMembersCommandTest.php
│   ├── Jobs/
│   │   ├── FetchCongressMembersPageJobTest.php
│   │   └── StoreMemberDataJobTest.php
│   └── CongressMembersFetchWorkflowTest.php
├── Unit/
│   ├── DTO/
│   │   ├── MemberDataTest.php
│   │   ├── MemberTermDataTest.php
│   │   └── PaginationDataTest.php
│   ├── Services/
│   │   └── CongressApiClientTest.php
│   ├── Repositories/
│   │   └── MemberRepositoryTest.php
│   └── UseCases/
│       └── FetchAndStoreCongressMembersUseCaseTest.php
└── TestCase.php
```

---

## Test Layers

---

# 1. **Unit Tests – DTOs (No Dependencies)**

### **Goal:** Ensure correct transformation from API raw data into DTOs.

### `tests/Unit/DTO/MemberDataTest.php`

Tests include:

-   Creating DTO with all fields
-   Handling optional fields
-   Graceful handling of missing nested data
-   Parsing `district` as integer
-   Creating nested `MemberTermData` objects
-   Supporting missing `depiction`
-   DTO immutability

### `tests/Unit/DTO/PaginationDataTest.php`

Tests include:

-   Creating pagination objects (with next page / without next page)
-   `hasNextPage()` logic
-   Immutability

---

# 2. **Unit Tests – Services (Mocked HTTP)**

### **Goal:** Test API interaction logic without real HTTP requests.

### `tests/Unit/Services/CongressApiClientTest.php`

Tests include:

-   Throws exception when API key missing
-   Fetch page successfully
-   Sends proper query params
-   Handles 401, 404, 500 errors
-   Parses empty member arrays
-   Handles missing member key
-   Handles invalid member data safely
-   Parses pagination
-   Correct HTTP configuration: headers, retries, timeouts

---

# 3. **Unit Tests – Repositories (SQLite In‑Memory)**

### **Goal:** Test persistence logic in isolation.

### `tests/Unit/Repositories/MemberRepositoryTest.php`

Tests include:

-   Find by `bioguide_id`
-   Create member
-   Create with terms
-   Update only when API date is newer
-   Skip update when local data is newer
-   Replace all terms on update
-   Wrap operations in DB transaction
-   Return member with terms loaded

---

# 4. **Unit Tests – UseCases (Mocked Dependencies)**

### **Goal:** Validate orchestration logic.

### `tests/Unit/UseCases/FetchAndStoreCongressMembersUseCaseTest.php`

Tests include:

-   Execute without limit
-   Execute with limit
-   Dispatch job to correct queue
-   Log process start
-   Exactly one dispatch per execution
-   Handle limit of zero
-   Handle large limit values

---

# 5. **Feature Tests – Jobs**

### **Goal:** Validate job flow with mocked dependencies.

---

## `tests/Feature/Jobs/FetchCongressMembersPageJobTest.php`

Tests include:

-   Fetch first page + dispatch store jobs
-   Dispatch next page job when pagination exists
-   Stop when no next page
-   Respect global limit
-   Stop when limit reached
-   Calculate next offset
-   Logging
-   Retry behavior

### `tests/Feature/Jobs/StoreMemberDataJobTest.php`

Tests include:

-   Store new member
-   Update existing member
-   Create associated terms
-   Log success
-   Retry logic

---

# 6. **Feature Tests – Console Command**

### `tests/Feature/Console/FetchCongressMembersCommandTest.php`

Tests include:

-   Executes without options
-   Executes with `--limit`
-   Validates limit option
-   Success message output
-   Dispatches job correctly
-   Returns status code 0

---

# 7. **End‑to‑End Workflow Test**

### `tests/Feature/CongressMembersFetchWorkflowTest.php`

Tests include:

-   Full workflow: Command → Jobs → Database
-   Multiple pages processed
-   Idempotency: repeated executions do not duplicate data
-   Limit respected across workflow
-   Terms created correctly
-   Updates applied correctly

---

# 📊 Test Coverage Summary

| Layer      | File                     | Tests   |
| ---------- | ------------------------ | ------- |
| DTO        | MemberDataTest           | 8       |
| DTO        | MemberTermDataTest       | 3       |
| DTO        | PaginationDataTest       | 5       |
| Service    | CongressApiClientTest    | 15      |
| Repository | MemberRepositoryTest     | 10      |
| UseCase    | FetchAndStoreUseCaseTest | 12      |
| Job        | FetchMembersPageJobTest  | 10      |
| Job        | StoreMemberDataJobTest   | 5       |
| Command    | FetchMembersCommandTest  | 6       |
| E2E        | FetchWorkflowTest        | 6       |
| **TOTAL**  |                          | **80+** |

---

## 🧭 Best Practices Applied

-   Clear **Arrange → Act → Assert** structure
-   No external API calls
-   Isolated, deterministic tests
-   Descriptive test names
-   `RefreshDatabase` where needed
-   Factories for realistic data

---

## 📝 Notes

-   SQLite in‑memory for high‑speed DB tests
-   `Http::fake()` for API mocking
-   `Queue::fake()` for job assertions
-   Logging mocked to prevent noise
-   Pest ensures cleaner syntax and faster execution
