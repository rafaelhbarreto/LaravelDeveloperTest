<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\MemberData;
use App\DTO\MemberListFiltersData;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MemberRepositoryInterface
{
    /**
     * Find a member by their bioguide ID.
     */
    public function findByBioguideId(string $bioguideId): ?Member;

    /**
     * Find a member by their bioguide ID with terms relationship loaded.
     */
    public function findByBioguideIdWithTerms(string $bioguideId): ?Member;

    /**
     * Get a filtered and paginated list of members.
     */
    public function getFilteredMembers(MemberListFiltersData $filters): LengthAwarePaginator;

    /**
     * Get distinct party names.
     */
    public function getDistinctParties(): Collection;

    /**
     * Get distinct state codes.
     */
    public function getDistinctStates(): Collection;

    /**
     * Create or update a member with their terms.
     * Implements idempotent behavior: only updates if API data is newer.
     *
     * Strategy: Deletes all existing terms and recreates them from the DTO.
     *
     * @return Member The created or updated member
     */
    public function upsertMember(MemberData $memberData): Member;
}
