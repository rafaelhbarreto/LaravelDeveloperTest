<?php

declare(strict_types=1);

use App\DTO\MemberData;
use App\DTO\MemberTermData;
use App\Models\Member;
use App\Models\MemberTerm;
use App\Repositories\MemberRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;

beforeEach(function () {
    $this->repository = new MemberRepository();
});

test('findByBioguideId returns member when exists', function () {
    $member = Member::factory()->create(['bioguide_id' => 'T000001']);

    $found = $this->repository->findByBioguideId('T000001');

    expect($found)->not->toBeNull()
        ->and($found->bioguide_id)->toBe('T000001')
        ->and($found->id)->toBe($member->id);
});

test('findByBioguideId returns null when member does not exist', function () {
    $found = $this->repository->findByBioguideId('NOTFOUND');

    expect($found)->toBeNull();
});

test('upsertMember creates new member with all attributes', function () {
    $memberData = new MemberData(
        bioguideId: 'T000001',
        name: 'Test Member',
        partyName: 'Test Party',
        state: 'TS',
        district: 5,
        updateDate: Carbon::parse('2024-01-15T10:00:00Z'),
        url: 'https://example.com',
        depictionAttribution: 'Test Attribution',
        depictionImageUrl: 'https://example.com/image.jpg',
        terms: new DataCollection(MemberTermData::class, [])
    );

    Log::shouldReceive('info')
        ->once()
        ->with('Created new member with terms', \Mockery::type('array'));

    $member = $this->repository->upsertMember($memberData);

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->bioguide_id)->toBe('T000001')
        ->and($member->name)->toBe('Test Member')
        ->and($member->party_name)->toBe('Test Party')
        ->and($member->state)->toBe('TS')
        ->and($member->district)->toBe(5)
        ->and($member->url)->toBe('https://example.com')
        ->and($member->depiction_attribution)->toBe('Test Attribution')
        ->and($member->depiction_image_url)->toBe('https://example.com/image.jpg');

    $this->assertDatabaseHas('members', ['bioguide_id' => 'T000001']);
});

test('upsertMember creates new member with terms', function () {
    $memberData = new MemberData(
        bioguideId: 'T000002',
        name: 'Test Member 2',
        partyName: null,
        state: null,
        district: null,
        updateDate: Carbon::parse('2024-01-15T10:00:00Z'),
        url: null,
        depictionAttribution: null,
        depictionImageUrl: null,
        terms: new DataCollection(MemberTermData::class, [
            new MemberTermData(
                chamber: 'House of Representatives',
                startYear: 2020,
                endYear: 2022
            ),
            new MemberTermData(
                chamber: 'Senate',
                startYear: 2022,
                endYear: null
            ),
        ])
    );

    Log::shouldReceive('info')->once();

    $member = $this->repository->upsertMember($memberData);

    expect($member->terms)->toHaveCount(2)
        ->and($member->terms[0]->chamber)->toBe('House of Representatives')
        ->and($member->terms[0]->start_year)->toBe(2020)
        ->and($member->terms[0]->end_year)->toBe(2022)
        ->and($member->terms[1]->chamber)->toBe('Senate')
        ->and($member->terms[1]->start_year)->toBe(2022)
        ->and($member->terms[1]->end_year)->toBeNull();

    $this->assertDatabaseCount('member_terms', 2);
});

test('upsertMember updates member when API data is newer', function () {
    $member = Member::factory()->create([
        'bioguide_id' => 'T000003',
        'name' => 'Old Name',
        'party_name' => 'Old Party',
        'updated_date' => Carbon::parse('2024-01-01T00:00:00Z'),
    ]);

    MemberTerm::factory()->create([
        'member_id' => $member->id,
        'chamber' => 'Old Chamber',
        'start_year' => 2018,
    ]);

    $memberData = new MemberData(
        bioguideId: 'T000003',
        name: 'New Name',
        partyName: 'New Party',
        state: 'NS',
        district: 10,
        updateDate: Carbon::parse('2024-01-15T10:00:00Z'),
        url: null,
        depictionAttribution: null,
        depictionImageUrl: null,
        terms: new DataCollection(MemberTermData::class, [
            new MemberTermData(
                chamber: 'New Chamber',
                startYear: 2020,
                endYear: 2024
            ),
        ])
    );

    Log::shouldReceive('info')
        ->once()
        ->with('Updated member and recreated terms', \Mockery::type('array'));

    $updated = $this->repository->upsertMember($memberData);

    expect($updated->id)->toBe($member->id)
        ->and($updated->name)->toBe('New Name')
        ->and($updated->party_name)->toBe('New Party')
        ->and($updated->state)->toBe('NS')
        ->and($updated->district)->toBe(10)
        ->and($updated->updated_date->toIso8601String())->toBe('2024-01-15T10:00:00+00:00');

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'name' => 'New Name',
        'party_name' => 'New Party',
    ]);
});

test('upsertMember skips update when local data is newer', function () {
    $member = Member::factory()->create([
        'bioguide_id' => 'T000004',
        'name' => 'Current Name',
        'updated_date' => Carbon::parse('2024-01-20T00:00:00Z'),
    ]);

    $memberData = new MemberData(
        bioguideId: 'T000004',
        name: 'Old Name',
        partyName: null,
        state: null,
        district: null,
        updateDate: Carbon::parse('2024-01-10T00:00:00Z'),
        url: null,
        depictionAttribution: null,
        depictionImageUrl: null,
        terms: new DataCollection(MemberTermData::class, [])
    );

    Log::shouldReceive('info')
        ->once()
        ->with('Skipping member update - local data is up-to-date', \Mockery::type('array'));

    $result = $this->repository->upsertMember($memberData);

    expect($result->id)->toBe($member->id)
        ->and($result->name)->toBe('Current Name');
});

test('upsertMember skips update when dates are equal', function () {
    $updateDate = Carbon::parse('2024-01-15T10:00:00Z');

    $member = Member::factory()->create([
        'bioguide_id' => 'T000005',
        'name' => 'Current Name',
        'updated_date' => $updateDate,
    ]);

    $memberData = new MemberData(
        bioguideId: 'T000005',
        name: 'Same Name',
        partyName: null,
        state: null,
        district: null,
        updateDate: $updateDate,
        url: null,
        depictionAttribution: null,
        depictionImageUrl: null,
        terms: new DataCollection(MemberTermData::class, [])
    );

    Log::shouldReceive('info')
        ->once()
        ->with('Skipping member update - local data is up-to-date', \Mockery::type('array'));

    $result = $this->repository->upsertMember($memberData);

    expect($result->name)->toBe('Current Name');
});

test('upsertMember deletes old terms and creates new ones on update', function () {
    $member = Member::factory()->create([
        'bioguide_id' => 'T000006',
        'updated_date' => Carbon::parse('2024-01-01T00:00:00Z'),
    ]);

    MemberTerm::factory()->count(3)->create(['member_id' => $member->id]);

    $memberData = new MemberData(
        bioguideId: 'T000006',
        name: 'Updated Member',
        partyName: null,
        state: null,
        district: null,
        updateDate: Carbon::parse('2024-01-15T10:00:00Z'),
        url: null,
        depictionAttribution: null,
        depictionImageUrl: null,
        terms: new DataCollection(MemberTermData::class, [
            new MemberTermData(
                chamber: 'New Chamber 1',
                startYear: 2024,
                endYear: null
            ),
            new MemberTermData(
                chamber: 'New Chamber 2',
                startYear: 2024,
                endYear: null
            ),
        ])
    );

    Log::shouldReceive('info')->once();

    $updated = $this->repository->upsertMember($memberData);

    expect($updated->terms)->toHaveCount(2)
        ->and($updated->terms[0]->chamber)->toBe('New Chamber 1')
        ->and($updated->terms[1]->chamber)->toBe('New Chamber 2');

    $this->assertDatabaseCount('member_terms', 2);
    $this->assertDatabaseMissing('member_terms', ['chamber' => 'Old Chamber']);
});

test('returns member with fresh terms relationship after creation', function () {
    $memberData = new MemberData(
        bioguideId: 'T000008',
        name: 'Test Member',
        partyName: null,
        state: null,
        district: null,
        updateDate: Carbon::parse('2024-01-15T10:00:00Z'),
        url: null,
        depictionAttribution: null,
        depictionImageUrl: null,
        terms: new DataCollection(MemberTermData::class, [
            new MemberTermData(
                chamber: 'House',
                startYear: 2020,
                endYear: 2024
            ),
        ])
    );

    Log::shouldReceive('info')->once();

    $member = $this->repository->upsertMember($memberData);

    expect($member->relationLoaded('terms'))->toBeTrue()
        ->and($member->terms)->toHaveCount(1);
});
