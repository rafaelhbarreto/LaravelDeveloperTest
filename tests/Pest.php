<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature');

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Unit/Repositories');

uses(Tests\TestCase::class)->in('Unit/DTO', 'Unit/Services', 'Unit/UseCases');

function mockCongressApiResponse(int $total = 250, int $perPage = 250, int $page = 1): array
{
    $members = [];
    for ($i = 0; $i < min($perPage, $total); $i++) {
        $bioguideId = 'T' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT);
        $members[] = [
            'bioguideId' => $bioguideId,
            'name' => "Test Member {$i}",
            'partyName' => 'Test Party',
            'state' => 'TS',
            'district' => $i + 1,
            'updateDate' => now()->toIso8601String(),
            'url' => "https://api.congress.gov/v3/member/{$bioguideId}",
            'depictionAttribution' => 'Test Attribution',
            'depictionImageUrl' => "https://example.com/images/{$bioguideId}.jpg",
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
    }

    return [
        'members' => $members,
        'pagination' => [
            'count' => count($members),
            'next' => $total > ($page * $perPage) ? "https://api.congress.gov/v3/member?offset=" . ($page * $perPage) : null,
        ],
    ];
}

function createMemberData(array $overrides = []): array
{
    return array_merge([
        'bioguideId' => 'T000001',
        'name' => 'Test Member',
        'partyName' => 'Test Party',
        'state' => 'TS',
        'district' => 1,
        'updateDate' => now()->toIso8601String(),
        'url' => 'https://api.congress.gov/v3/member/T000001',
        'depictionAttribution' => 'Test Attribution',
        'depictionImageUrl' => 'https://example.com/images/T000001.jpg',
        'terms' => [
            'item' => [
                [
                    'chamber' => 'House of Representatives',
                    'startYear' => 2020,
                    'endYear' => 2022,
                ],
            ],
        ],
    ], $overrides);
}
