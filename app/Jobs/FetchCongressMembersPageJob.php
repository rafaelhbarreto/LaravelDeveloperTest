<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\CongressApiClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchCongressMembersPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_TRIES = 3;

    public int $tries = self::MAX_TRIES;

    public function __construct(
        private readonly int $offset,
        private readonly ?int $limit,
        private readonly int $chunkSize,
        private readonly int $totalProcessed,
    ) {
    }

    /**
     * Handle the fetch of the Congress members page.
     *
     * @param CongressApiClientInterface $apiClient The API client
     * @return void
     */
    public function handle(CongressApiClientInterface $apiClient): void
    {
        $recordsToFetch = $this->calculateRecordsToFetch();

        $this->logFetchStart($recordsToFetch);

        $result = $apiClient->fetchMembersPage($recordsToFetch, $this->offset);

        $members = $result['members'];
        $pagination = $result['pagination'];

        $this->logFetchComplete($members, $pagination);

        $this->dispatchStoreJobs($members);

        $newTotalProcessed = $this->totalProcessed + count($members);

        if ($this->shouldFetchNextPage($pagination, $newTotalProcessed)) {
            $this->dispatchNextFetchJob($newTotalProcessed, count($members));
        } else {
            $this->logFetchFinished($newTotalProcessed, $pagination);
        }
    }

    /**
     * Log the failure of the job.
     *
     * @param \Throwable $exception The exception that caused the failure
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FetchCongressMembersPageJob failed', [
            'offset' => $this->offset,
            'chunk_size' => $this->chunkSize,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Calculate the number of records to fetch.
     *
     * @return int The number of records to fetch
     */
    private function calculateRecordsToFetch(): int
    {
        if ($this->limit === null) {
            return $this->chunkSize;
        }

        $remainingRecords = $this->limit - $this->totalProcessed;

        return min($this->chunkSize, $remainingRecords);
    }

    /**
     * Log the start of the fetch.
     *
     * @param int $recordsToFetch The number of records to fetch
     * @return void
     */
    private function logFetchStart(int $recordsToFetch): void
    {
        Log::info('Fetching Congress members page', [
            'offset' => $this->offset,
            'chunk_size' => $this->chunkSize,
            'records_to_fetch' => $recordsToFetch,
            'total_processed' => $this->totalProcessed,
            'limit' => $this->limit ?? 'unlimited',
        ]);
    }

    /**
     * Log the completion of the fetch.
     *
     * @param array $members The members data
     * @param $pagination The pagination data
     * @return void
     */
    private function logFetchComplete(array $members, $pagination): void
    {
        Log::info('Fetched members from API', [
            'count' => count($members),
            'has_next' => $pagination->hasNextPage(),
        ]);
    }

    /**
     * Dispatch the store jobs for the members.
     *
     * @param array $members The members data
     * @return void
     */
    private function dispatchStoreJobs(array $members): void
    {
        foreach ($members as $memberData) {
            StoreMemberDataJob::dispatch($memberData)
                ->onQueue(config('congress.queues.store'));
        }
    }

    /**
     * Check if the next page should be fetched.
     *
     * @param $pagination The pagination data
     * @param int $newTotalProcessed The total number of records processed
     * @return bool
     */
    private function shouldFetchNextPage($pagination, int $newTotalProcessed): bool
    {
        return $pagination->hasNextPage()
            && ($this->limit === null || $newTotalProcessed < $this->limit);
    }

    /**
     * Dispatch the next fetch job to continue the chain.
     *
     * @param int $newTotalProcessed The total number of records processed
     * @param int $membersCount The number of members fetched in the current page
     * @return void
     */
    private function dispatchNextFetchJob(int $newTotalProcessed, int $membersCount): void
    {
        $nextOffset = $this->offset + $membersCount;

        Log::info('Dispatching next fetch job', [
            'next_offset' => $nextOffset,
            'total_processed' => $newTotalProcessed,
        ]);

        self::dispatch(
            offset: $nextOffset,
            limit: $this->limit,
            chunkSize: $this->chunkSize,
            totalProcessed: $newTotalProcessed
        )->onQueue(config('congress.queues.fetch'));
    }

    /**
     * Log the completion of the fetch.
     *
     * @param int $newTotalProcessed The total number of records processed
     * @param $pagination The pagination data
     * @return void
     */
    private function logFetchFinished(int $newTotalProcessed, $pagination): void
    {
        Log::info('Fetch complete', [
            'total_processed' => $newTotalProcessed,
            'reason' => !$pagination->hasNextPage() ? 'no_more_pages' : 'limit_reached',
        ]);
    }
}
