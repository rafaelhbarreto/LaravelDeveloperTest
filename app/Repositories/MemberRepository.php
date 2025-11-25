<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MemberRepositoryInterface;
use App\DTO\MemberData;
use App\DTO\MemberListFiltersData;
use App\DTO\MemberTermData;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberRepository implements MemberRepositoryInterface
{
    private const ALLOWED_SORT_COLUMNS = ['name', 'party_name', 'state', 'district', 'updated_date'];

    public function findByBioguideId(string $bioguideId): ?Member
    {
        return Member::where('bioguide_id', $bioguideId)->first();
    }

    public function findByBioguideIdWithTerms(string $bioguideId): ?Member
    {
        return Member::where('bioguide_id', $bioguideId)
            ->with('terms')
            ->first();
    }

    public function getFilteredMembers(MemberListFiltersData $filters): LengthAwarePaginator
    {
        $query = Member::query()->with('terms');

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->paginate($filters->perPage)->withQueryString();
    }

    public function getDistinctParties(): Collection
    {
        return Member::query()
            ->distinct()
            ->whereNotNull('party_name')
            ->pluck('party_name')
            ->sort()
            ->values();
    }

    public function getDistinctStates(): Collection
    {
        return Member::query()
            ->distinct()
            ->whereNotNull('state')
            ->pluck('state')
            ->sort()
            ->values();
    }

    public function upsertMember(MemberData $memberData): Member
    {
        return DB::transaction(function () use ($memberData) {
            $existingMember = $this->findByBioguideId($memberData->bioguideId);

            return $existingMember !== null
                ? $this->handleExistingMember($existingMember, $memberData)
                : $this->createNewMember($memberData);
        });
    }

    private function handleExistingMember(Member $existingMember, MemberData $memberData): Member
    {
        if (!$this->shouldUpdateMember($existingMember, $memberData)) {
            $this->logSkippedUpdate($existingMember, $memberData);
            return $existingMember;
        }

        return $this->updateExistingMember($existingMember, $memberData);
    }

    private function shouldUpdateMember(Member $existingMember, MemberData $memberData): bool
    {
        return $existingMember->updated_date->lessThan($memberData->updateDate);
    }

    private function updateExistingMember(Member $member, MemberData $memberData): Member
    {
        $member->update($this->prepareMemberAttributes($memberData));

        $this->syncMemberTerms($member, $memberData);

        $this->logMemberUpdate($memberData);

        return $member->fresh(['terms']);
    }

    private function createNewMember(MemberData $memberData): Member
    {
        $attributes = $this->prepareMemberAttributes($memberData);
        $attributes['bioguide_id'] = $memberData->bioguideId;

        $member = Member::create($attributes);

        $this->createMemberTerms($member, $memberData);

        $this->logMemberCreation($memberData);

        return $member->fresh(['terms']);
    }

    private function syncMemberTerms(Member $member, MemberData $memberData): void
    {
        $member->terms()->delete();
        $this->createMemberTerms($member, $memberData);
    }

    private function createMemberTerms(Member $member, MemberData $memberData): void
    {
        foreach ($memberData->terms as $termData) {
            $member->terms()->create($this->prepareTermAttributes($termData));
        }
    }

    private function prepareMemberAttributes(MemberData $memberData): array
    {
        return [
            'depiction_attribution' => $memberData->depictionAttribution,
            'depiction_image_url' => $memberData->depictionImageUrl,
            'name' => $memberData->name,
            'party_name' => $memberData->partyName,
            'state' => $memberData->state,
            'district' => $memberData->district,
            'updated_date' => $memberData->updateDate,
            'url' => $memberData->url,
        ];
    }

    private function prepareTermAttributes(MemberTermData $termData): array
    {
        return [
            'chamber' => $termData->chamber,
            'start_year' => $termData->startYear,
            'end_year' => $termData->endYear,
        ];
    }

    private function logSkippedUpdate(Member $existingMember, MemberData $memberData): void
    {
        Log::info('Skipping member update - local data is up-to-date', [
            'bioguide_id' => $memberData->bioguideId,
            'local_date' => $existingMember->updated_date->toIso8601String(),
            'api_date' => $memberData->updateDate->toIso8601String(),
        ]);
    }

    private function logMemberUpdate(MemberData $memberData): void
    {
        Log::info('Updated member and recreated terms', [
            'bioguide_id' => $memberData->bioguideId,
            'terms_count' => $memberData->terms->count(),
        ]);
    }

    private function logMemberCreation(MemberData $memberData): void
    {
        Log::info('Created new member with terms', [
            'bioguide_id' => $memberData->bioguideId,
            'terms_count' => $memberData->terms->count(),
        ]);
    }

    private function applyFilters(Builder $query, MemberListFiltersData $filters): void
    {
        if ($filters->name !== null) {
            $this->applyNameFilter($query, $filters->name);
        }

        if ($filters->party !== null) {
            $this->applyPartyFilter($query, $filters->party);
        }

        if ($filters->state !== null) {
            $this->applyStateFilter($query, $filters->state);
        }
    }

    private function applyNameFilter(Builder $query, string $name): void
    {
        $query->where('name', 'ilike', "%{$name}%");
    }

    private function applyPartyFilter(Builder $query, string $party): void
    {
        $query->where('party_name', $party);
    }

    private function applyStateFilter(Builder $query, string $state): void
    {
        $query->where('state', $state);
    }

    private function applySorting(Builder $query, MemberListFiltersData $filters): void
    {
        if ($this->isValidSortColumn($filters->sortBy)) {
            $query->orderBy($filters->sortBy, $filters->sortDirection);
        } else {
            $query->orderBy('updated_date', 'desc');
        }
    }

    private function isValidSortColumn(string $column): bool
    {
        return in_array($column, self::ALLOWED_SORT_COLUMNS, true);
    }
}
