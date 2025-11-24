<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MemberRepositoryInterface;
use App\DTO\MemberData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreMemberDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_TRIES = 3;

    public int $tries = self::MAX_TRIES;

    public function __construct(
        private readonly MemberData $memberData,
    ) {
    }

    /**
     * Handle the storing of the member data.
     *
     * @param MemberRepositoryInterface $repository The repository
     * @return void
     */
    public function handle(MemberRepositoryInterface $repository): void
    {
        $this->logStoringStart();

        $member = $repository->upsertMember($this->memberData);

        $this->logStoringComplete($member->id);
    }

    /**
     * Log the failure of the job.
     *
     * @param \Throwable $exception The exception that caused the failure
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('StoreMemberDataJob failed', [
            'bioguide_id' => $this->memberData->bioguideId,
            'name' => $this->memberData->name,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Log the start of the storing.
     *
     * @return void
     */
    private function logStoringStart(): void
    {
        Log::debug('Storing member data', [
            'bioguide_id' => $this->memberData->bioguideId,
            'name' => $this->memberData->name,
        ]);
    }

    /**
     * Log the completion of the storing.
     *
     * @param int $memberId The ID of the member
     * @return void
     */
    private function logStoringComplete(int $memberId): void
    {
        Log::debug('Member data stored', [
            'id' => $memberId,
            'bioguide_id' => $this->memberData->bioguideId,
        ]);
    }
}
