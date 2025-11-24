<?php

declare(strict_types=1);

use App\DTO\MemberData;
use App\DTO\MemberTermData;
use Carbon\Carbon;
use Spatie\LaravelData\DataCollection;

test('creates member data from API response with all fields', function () {
    $apiResponse = [
        'bioguideId' => 'T000001',
        'name' => 'Test Member',
        'partyName' => 'Test Party',
        'state' => 'TS',
        'district' => 1,
        'updateDate' => '2024-01-15T10:30:00Z',
        'url' => 'https://api.congress.gov/v3/member/T000001',
        'depiction' => [
            'attribution' => 'Test Attribution',
            'imageUrl' => 'https://example.com/images/T000001.jpg',
        ],
        'terms' => [
            'item' => [
                [
                    'chamber' => 'House of Representatives',
                    'startYear' => 2020,
                    'endYear' => 2022,
                ],
                [
                    'chamber' => 'Senate',
                    'startYear' => 2022,
                ],
            ],
        ],
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect($memberData)->toBeInstanceOf(MemberData::class)
        ->and($memberData->bioguideId)->toBe('T000001')
        ->and($memberData->name)->toBe('Test Member')
        ->and($memberData->partyName)->toBe('Test Party')
        ->and($memberData->state)->toBe('TS')
        ->and($memberData->district)->toBe(1)
        ->and($memberData->updateDate)->toBeInstanceOf(Carbon::class)
        ->and($memberData->updateDate->toIso8601String())->toBe('2024-01-15T10:30:00+00:00')
        ->and($memberData->url)->toBe('https://api.congress.gov/v3/member/T000001')
        ->and($memberData->depictionAttribution)->toBe('Test Attribution')
        ->and($memberData->depictionImageUrl)->toBe('https://example.com/images/T000001.jpg')
        ->and($memberData->terms)->toBeInstanceOf(DataCollection::class)
        ->and($memberData->terms)->toHaveCount(2);
});

test('creates member data from API response with optional fields missing', function () {
    $apiResponse = [
        'bioguideId' => 'T000002',
        'name' => 'Test Member 2',
        'updateDate' => '2024-01-15T10:30:00Z',
        'terms' => [
            'item' => [],
        ],
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect($memberData->bioguideId)->toBe('T000002')
        ->and($memberData->name)->toBe('Test Member 2')
        ->and($memberData->partyName)->toBeNull()
        ->and($memberData->state)->toBeNull()
        ->and($memberData->district)->toBeNull()
        ->and($memberData->url)->toBeNull()
        ->and($memberData->depictionAttribution)->toBeNull()
        ->and($memberData->depictionImageUrl)->toBeNull()
        ->and($memberData->terms)->toHaveCount(0);
});

test('handles missing terms gracefully', function () {
    $apiResponse = [
        'bioguideId' => 'T000003',
        'name' => 'Test Member 3',
        'updateDate' => '2024-01-15T10:30:00Z',
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect($memberData->terms)->toBeInstanceOf(DataCollection::class)
        ->and($memberData->terms)->toHaveCount(0);
});

test('handles non-array terms gracefully', function () {
    $apiResponse = [
        'bioguideId' => 'T000004',
        'name' => 'Test Member 4',
        'updateDate' => '2024-01-15T10:30:00Z',
        'terms' => [
            'item' => 'invalid',
        ],
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect($memberData->terms)->toBeInstanceOf(DataCollection::class)
        ->and($memberData->terms)->toHaveCount(0);
});

test('creates member term data objects from nested API response', function () {
    $apiResponse = [
        'bioguideId' => 'T000005',
        'name' => 'Test Member 5',
        'updateDate' => '2024-01-15T10:30:00Z',
        'terms' => [
            'item' => [
                [
                    'chamber' => 'House of Representatives',
                    'startYear' => 2020,
                    'endYear' => 2022,
                ],
            ],
        ],
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect($memberData->terms)->toHaveCount(1)
        ->and($memberData->terms->first())->toBeInstanceOf(MemberTermData::class)
        ->and($memberData->terms->first()->chamber)->toBe('House of Representatives')
        ->and($memberData->terms->first()->startYear)->toBe(2020)
        ->and($memberData->terms->first()->endYear)->toBe(2022);
});

test('parses district as integer from API response', function () {
    $apiResponse = [
        'bioguideId' => 'T000006',
        'name' => 'Test Member 6',
        'updateDate' => '2024-01-15T10:30:00Z',
        'district' => '5',
        'terms' => ['item' => []],
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect($memberData->district)->toBe(5)
        ->and($memberData->district)->toBeInt();
});

test('handles missing depiction object', function () {
    $apiResponse = [
        'bioguideId' => 'T000007',
        'name' => 'Test Member 7',
        'updateDate' => '2024-01-15T10:30:00Z',
        'terms' => ['item' => []],
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect($memberData->depictionAttribution)->toBeNull()
        ->and($memberData->depictionImageUrl)->toBeNull();
});

test('member data is readonly', function () {
    $apiResponse = [
        'bioguideId' => 'T000008',
        'name' => 'Test Member 8',
        'updateDate' => '2024-01-15T10:30:00Z',
        'terms' => ['item' => []],
    ];

    $memberData = MemberData::fromApiResponse($apiResponse);

    expect(fn () => $memberData->bioguideId = 'T000999')
        ->toThrow(Error::class);
});
