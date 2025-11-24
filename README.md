## About

This is an test application to read data from https://api.congress.gov, storage in database and list on screen.

## Getting Started

### Prerequisites

-   **PHP**: 8.1 or higher
-   **Laravel**: 12.x
-   **Docker**: MySQL, PostgreSQL, or SQLite (your choice)
-   **Composer**: Latest stable version
-   **Node.js**: 18.x or higher (for asset compilation)

### Installation

-   Clone the repository
-   Install all composer packages
-   Copy `.env.example` to `.env`
-   Create an application key. In yout termninal type `sail artisan key:generate`
-   Set your `API_KEY` from congress.gov on.env
-   Set database keys on `.env` file. By default the configuration is appointing to Docker container
-   Run migrations `sail artisan migrate`

### Importing data

-   Run the two queues: `congress-fetch` and `congress-storage`
    -   `sail artisan queue:work --queue congress-fetch`
    -   `sail artisan queue:work --queue congress-storage`
-   Run the artisan command to dispatch the jobs
    -   `sail artisan congress:fetch-members --limit=500` (limit is an opitional param. If not given, get all available data)

### Running the Application

-   Access [http://localhost/congress/members] URL
-   Manipulate the view

### Running Tests

-   Run the test suite: `sail test`
