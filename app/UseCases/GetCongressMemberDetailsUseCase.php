<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Contracts\MemberRepositoryInterface;
use App\Http\Resources\MemberResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class GetCongressMemberDetailsUseCase
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository
    ) {}

    /**
     * Execute the use case to get details of a specific Congress member.
     *
     * @throws ModelNotFoundException
     */
    public function execute(string $bioguideId): array
    {
        $this->logFetchAttempt($bioguideId);

        $member = $this->findMember($bioguideId);

        $this->logFetchSuccess($bioguideId);

        return $this->transformMemberData($member);
    }

    private function findMember(string $bioguideId): \App\Models\Member
    {
        $member = $this->memberRepository->findByBioguideIdWithTerms($bioguideId);

        if ($member === null) {
            $this->logMemberNotFound($bioguideId);

            throw new ModelNotFoundException(
                "Congress member with bioguide ID '{$bioguideId}' not found."
            );
        }

        return $member;
    }

    private function transformMemberData(\App\Models\Member $member): array
    {
        return (new MemberResource($member))->resolve();
    }

    private function logFetchAttempt(string $bioguideId): void
    {
        Log::info('Fetching Congress member details', [
            'bioguide_id' => $bioguideId,
        ]);
    }

    private function logFetchSuccess(string $bioguideId): void
    {
        Log::debug('Successfully fetched Congress member details', [
            'bioguide_id' => $bioguideId,
        ]);
    }

    private function logMemberNotFound(string $bioguideId): void
    {
        Log::warning('Congress member not found', [
            'bioguide_id' => $bioguideId,
        ]);
    }
}
