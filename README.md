## About

This is an test application to read data from https://api.congress.gov, storage in database and list on screen.

## Getting Started

### Prerequisites

-   **PHP**: 8.2 or higher
-   **Docker** and **Docker compose**
-   **Composer**: Latest stable version
-   **Node.js**: 18.x or higher (for asset compilation)

### Installation

#### note

** I'm considering that in your terminal doesen't have the sail alias installed. **

-   Clone the repository
-   Install all composer packages
    -   `composer install`
-   Copy `.env.example` to `.env`
    -   `cp .env.example .env`
-   Up the application
    -   `./vendor/bin/sail up -d`
-   Create an application key.
    -   In yout termninal type `./vendor/bin/sail artisan key:generate`
-   Set your `CONGRESS_API_KEY` from congress.gov on `.env` (Sing up on [https://api.congress.gov])
-   Run migrations `./vendor/bin/sail artisan migrate`

### Importing data

-   Run the two queues: `congress-fetch` and `congress-storage`
    -   `./vendor/bin/sail artisan queue:work --queue congress-fetch,congress-store`
-   Run the artisan command to dispatch the jobs
    -   `./vendor/bin/sail artisan congress:fetch-members --limit=1000` (limit is an opitional param. If not given, get all available data)

### Running the Application

-   Install the npm packages `./vendor/bin/sail npm install`
-   Build the assets `./vendor/bin/sail npm run build`
-   Access [http://localhost/congress/members] URL
-   Manipulate the view

### Running Tests

-   Run the test suite: `./vendor/bin/sail test`
