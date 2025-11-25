<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Jobs\FetchCongressMembersPageJob;
use Illuminate\Support\Facades\Log;

class FetchAndStoreCongressMembersUseCase
{
    public function execute(?int $limit = null): void
    {
        $chunkSize = config('congress.api.chunk_size');

        $this->logFetchStart($limit, $chunkSize);

        $this->dispatchInitialFetchJob($limit, $chunkSize);

        $this->logJobDispatched();
    }

    /**
     * Log the start of the Congress members fetch.
     *
     * @param int|null $limit Maximum number of records to fetch (null = fetch all)
     * @param int $chunkSize The size of the chunk to fetch
     */
    private function logFetchStart(?int $limit, int $chunkSize): void
    {
        Log::info('Starting Congress members fetch', [
            'limit' => $limit ?? 'unlimited',
            'chunk_size' => $chunkSize,
        ]);
    }

    /**
     * Dispatch the initial fetch job to start the chain.
     *
     * @param int|null $limit Maximum number of records to fetch (null = fetch all)
     * @param int $chunkSize The size of the chunk to fetch
     */
    private function dispatchInitialFetchJob(?int $limit, int $chunkSize): void
    {
        FetchCongressMembersPageJob::dispatch(
            offset: 0,
            limit: $limit,
            chunkSize: $chunkSize,
            totalProcessed: 0
        )->onQueue(config('congress.queues.fetch'));
    }

    /**
     * Log the dispatch of the initial fetch job.
     *
     * @return void
     */
    private function logJobDispatched(): void
    {
        Log::info('Initial fetch job dispatched');
    }
}
