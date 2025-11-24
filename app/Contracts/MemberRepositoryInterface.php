<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\MemberData;
use App\Models\Member;

interface MemberRepositoryInterface
{
    /**
     * Find a member by their bioguide ID.
     */
    public function findByBioguideId(string $bioguideId): ?Member;

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
