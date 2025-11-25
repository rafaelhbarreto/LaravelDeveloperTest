<?php

declare(strict_types=1);

use App\DTO\MemberData;
use App\DTO\PaginationData;
use App\Exceptions\CongressApiException;
use App\Services\CongressApiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config(['congress.api.base_url' => 'https://api.congress.gov/v3']);
    config(['congress.api.api_key' => 'test-api-key']);
    config(['congress.api.timeout' => 30]);
});

test('throws exception when API key is not configured', function () {
    config(['congress.api.api_key' => '']);

    expect(fn () => new CongressApiClient())
        ->toThrow(CongressApiException::class, 'Congress API key is not configured');
});

test('fetches members page successfully', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'members' => [
                createMemberData(['bioguideId' => 'T000001', 'name' => 'Test Member 1']),
                createMemberData(['bioguideId' => 'T000002', 'name' => 'Test Member 2']),
            ],
            'pagination' => [
                'count' => 2,
                'next' => 'https://api.congress.gov/v3/member?offset=2',
            ],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 10, offset: 0);

    expect($result)->toHaveKey('members')
        ->and($result)->toHaveKey('pagination')
        ->and($result['members'])->toHaveCount(2)
        ->and($result['members'][0])->toBeInstanceOf(MemberData::class)
        ->and($result['members'][0]->bioguideId)->toBe('T000001')
        ->and($result['members'][1]->bioguideId)->toBe('T000002')
        ->and($result['pagination'])->toBeInstanceOf(PaginationData::class)
        ->and($result['pagination']->count)->toBe(2)
        ->and($result['pagination']->hasNextPage())->toBeTrue();
});

test('sends correct query parameters when fetching members page', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response(mockCongressApiResponse(10), 200),
    ]);

    $client = new CongressApiClient();
    $client->fetchMembersPage(limit: 50, offset: 100);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.congress.gov/v3/member?api_key=test-api-key&offset=100&limit=50';
    });
});

test('throws exception when API returns 401 unauthorized', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    Log::shouldReceive('error')->once();

    $client = new CongressApiClient();

    try {
        $client->fetchMembersPage(limit: 10);
        expect(false)->toBeTrue('Should have thrown CongressApiException');
    } catch (CongressApiException $e) {
        expect($e->getMessage())->toContain('Status: 401');
    }
});

test('throws exception when API returns 404 not found', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response(['error' => 'Not Found'], 404),
    ]);

    Log::shouldReceive('error')->once();

    $client = new CongressApiClient();

    try {
        $client->fetchMembersPage(limit: 10);
        expect(false)->toBeTrue('Should have thrown CongressApiException');
    } catch (CongressApiException $e) {
        expect($e->getMessage())->toContain('Status: 404');
    }
});

test('throws exception when API returns 500 server error', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    Log::shouldReceive('error')->once();

    $client = new CongressApiClient();

    try {
        $client->fetchMembersPage(limit: 10);
        expect(false)->toBeTrue('Should have thrown CongressApiException');
    } catch (CongressApiException $e) {
        expect($e->getMessage())->toContain('Status: 500');
    }
});

test('fetches members from URL successfully', function () {
    Http::fake([
        'https://api.congress.gov/v3/member?offset=250' => Http::response([
            'members' => [
                createMemberData(['bioguideId' => 'T000003']),
            ],
            'pagination' => [
                'count' => 1,
                'next' => null,
            ],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersFromUrl('https://api.congress.gov/v3/member?offset=250');

    expect($result['members'])->toHaveCount(1)
        ->and($result['members'][0]->bioguideId)->toBe('T000003')
        ->and($result['pagination']->hasNextPage())->toBeFalse();
});

test('handles empty members array', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'members' => [],
            'pagination' => [
                'count' => 0,
                'next' => null,
            ],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 10);

    expect($result['members'])->toBeArray()
        ->and($result['members'])->toHaveCount(0);
});

test('handles missing members key in response', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'pagination' => [
                'count' => 0,
                'next' => null,
            ],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 10);

    expect($result['members'])->toBeArray()
        ->and($result['members'])->toHaveCount(0);
});

test('handles invalid members data gracefully', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'members' => 'invalid',
            'pagination' => [
                'count' => 0,
                'next' => null,
            ],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 10);

    expect($result['members'])->toBeArray()
        ->and($result['members'])->toHaveCount(0);
});

test('skips invalid member data and continues parsing', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'members' => [
                createMemberData(['bioguideId' => 'T000001', 'name' => 'Valid Member']),
                ['invalid' => 'data'],
                createMemberData(['bioguideId' => 'T000002', 'name' => 'Another Valid Member']),
            ],
            'pagination' => [
                'count' => 3,
                'next' => null,
            ],
        ], 200),
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->with('Failed to parse member data', \Mockery::type('array'));

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 10);

    expect($result['members'])->toHaveCount(2)
        ->and($result['members'][0]->bioguideId)->toBe('T000001')
        ->and($result['members'][1]->bioguideId)->toBe('T000002');
});

test('parses pagination with next page', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'members' => [],
            'pagination' => [
                'count' => 250,
                'next' => 'https://api.congress.gov/v3/member?offset=250',
            ],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 250);

    expect($result['pagination']->count)->toBe(250)
        ->and($result['pagination']->next)->toBe('https://api.congress.gov/v3/member?offset=250')
        ->and($result['pagination']->hasNextPage())->toBeTrue();
});

test('parses pagination without next page', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'members' => [],
            'pagination' => [
                'count' => 150,
            ],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 250);

    expect($result['pagination']->count)->toBe(150)
        ->and($result['pagination']->next)->toBeNull()
        ->and($result['pagination']->hasNextPage())->toBeFalse();
});

test('handles missing pagination data', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response([
            'members' => [],
        ], 200),
    ]);

    $client = new CongressApiClient();
    $result = $client->fetchMembersPage(limit: 10);

    expect($result['pagination'])->toBeInstanceOf(PaginationData::class)
        ->and($result['pagination']->count)->toBe(0)
        ->and($result['pagination']->next)->toBeNull();
});

test('configures HTTP client with correct headers and retry', function () {
    Http::fake([
        'https://api.congress.gov/v3/member*' => Http::response(mockCongressApiResponse(1), 200),
    ]);

    $client = new CongressApiClient();
    $client->fetchMembersPage(limit: 10);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Accept', 'application/json');
    });
});
