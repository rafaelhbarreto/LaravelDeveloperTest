<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MemberRepositoryInterface;
use App\DTO\MemberData;
use App\DTO\MemberTermData;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberRepository implements MemberRepositoryInterface
{
    public function findByBioguideId(string $bioguideId): ?Member
    {
        return Member::where('bioguide_id', $bioguideId)->first();
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
}
