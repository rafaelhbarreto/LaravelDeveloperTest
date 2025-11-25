<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\UseCases\FetchAndStoreCongressMembersUseCase;
use Illuminate\Console\Command;

class FetchCongressMembersCommand extends Command
{
    protected $signature = 'congress:fetch-members
                            {--limit= : Maximum number of members to fetch (optional, fetches all if not specified)}';

    protected $description = 'Fetch members from the US Congress API and store them in the database';

    /**
     * Handle the fetch of the Congress members.
     *
     * @param FetchAndStoreCongressMembersUseCase $useCase The use case
     * @return int The status code
     */
    public function handle(FetchAndStoreCongressMembersUseCase $useCase): int
    {
        $limit = $this->parseAndValidateLimit();

        if ($limit === false) {
            return self::FAILURE;
        }

        $this->displayStartMessage($limit);

        try {
            $useCase->execute($limit);
            $this->displaySuccessMessage();

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->displayErrorMessage($e);

            return self::FAILURE;
        }
    }

    /**
     * Parse and validate the limit.
     *
     * @return int|null|false The limit of the fetch
     */
    private function parseAndValidateLimit(): int|null|false
    {
        $limit = $this->option('limit');

        if ($limit === null) {
            return null;
        }

        $limit = (int) $limit;

        if ($limit <= 0) {
            $this->error('The limit must be a positive integer.');
            return false;
        }

        return $limit;
    }

    /**
     * Display the start message.
     *
     * @param int|null $limit The limit of the fetch
     * @return void
     */
    private function displayStartMessage(?int $limit): void
    {
        $this->info('Starting Congress members fetch...');

        $limitMessage = $limit !== null
            ? "Limit: {$limit} members"
            : 'Limit: Fetch all available members';

        $this->info($limitMessage);
    }

    /**
     * Display the success message.
     *
     * @return void
     */
    private function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info('✓ Fetch jobs have been dispatched to the queue.');
        $this->info('  Processing will happen asynchronously.');
        $this->newLine();
        $this->comment('Make sure your queue worker is running:');
        $this->line('  php artisan queue:work');
        $this->newLine();
    }

    /**
     * Display the error message.
     *
     * @param \Throwable $e The exception that caused the failure
     * @return void
     */
    private function displayErrorMessage(\Throwable $e): void
    {
        $this->error('Failed to dispatch fetch jobs: ' . $e->getMessage());

        if ($this->option('verbose')) {
            $this->error($e->getTraceAsString());
        }
    }
}
