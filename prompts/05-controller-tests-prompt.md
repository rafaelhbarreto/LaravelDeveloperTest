# Prompt 5 – Tests for CongressMemberController

## Context

After implementing the frontend I need to create unit and integration tests for the `CongressMemberController` that exposes the routes consumed by the frontend.  
The tests must cover both controller methods (`index` and `show`) with **all external dependencies mocked** (database, API, etc.).

---

## Goal

Create a complete test suite using **Pest PHP** that guarantees:

1. **Full isolation** – No real external dependencies
2. **Full coverage** – All scenarios (happy path, edge cases, error states)
3. **Fast execution** – Tests should run in milliseconds
4. **Maintainability** – Clean, organized, and readable test code

---

## Requirements

### Controller Under Test

**File:** `app/Http/Controllers/CongressMemberController.php`

### Methods

1. `index(Request $request): View`

    - Filters: `name`, `party`, `state`
    - Sorting: `sort_by`, `sort_direction`
    - Pagination: `per_page`
    - Returns view: `congress.members.index` (Inertia or Blade, depending on setup)

2. `show(string $bioguideId): View`

    - Fetches member by `bioguide_id`
    - Loads `terms` relationship
    - Returns view: `congress.members.show`
    - Returns **404** if member not found

---

## Test Structure

### Directories

```txt
tests/
├── Unit/Controllers/
│   └── CongressMemberControllerTest.php       (10 tests)
└── Feature/Controllers/
    └── CongressMemberControllerTest.php       (22 tests)
```

---

## Types of Tests

### 1. Unit Tests (Full Isolation)

-   Test controller logic without Laravel HTTP layer
-   Mock use cases and view factory
-   Assert that the correct methods are called with the correct parameters
-   Do **not** hit database or real views
-   **Expected count:** 10 tests

Typical checks:

-   Filters and pagination are parsed correctly from `Request`
-   Default values are applied when parameters are missing
-   Correct DTOs / arrays are passed to the view
-   `show()` calls the appropriate use case with the proper `bioguideId`
-   `show()` correctly triggers a 404 (e.g. via `abort(404)` or `ModelNotFoundException`)

---

### 2. Feature Tests (HTTP Integration)

-   Use real HTTP layer (`$this->get()` from Laravel’s test framework)
-   Mock only the **use cases** (no real DB, no real API)
-   Assert:
    -   Status codes
    -   Views rendered
    -   Data passed to the view
-   **Expected count:** 22 tests

Typical checks:

-   Routes are correctly registered
-   Filters are accepted and passed correctly
-   Sorting and pagination parameters work as expected
-   Show route handles existing and non-existing members correctly

## Implementation Notes

-   Use **Mockery** (or Pest’s mocking helpers) for mocking dependencies in unit tests
-   Feature tests should use `$this->get()` and `$this->getJson()` where appropriate
-   **View factory** MUST be mocked in unit tests to avoid rendering overhead
-   Mock data must contain **all fields the view expects**
-   For 404 cases, the controller should throw or propagate `ModelNotFoundException` or explicitly call `abort(404)`

---

## Summary

This test plan ensures that:

-   The `CongressMemberController` is fully covered at both unit and feature levels
-   All edge cases for filters, sorting, and pagination are tested
-   Error scenarios (such as member not found) are handled correctly
-   Tests are fast, reliable, and maintainable, aligned with the rest of the Congress members module (Prompts 1–4).
