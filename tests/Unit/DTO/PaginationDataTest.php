<?php

declare(strict_types=1);

use App\DTO\PaginationData;

test('creates pagination data with next page', function () {
    $paginationData = new PaginationData(
        count: 250,
        next: 'https://api.congress.gov/v3/member?offset=250'
    );

    expect($paginationData->count)->toBe(250)
        ->and($paginationData->next)->toBe('https://api.congress.gov/v3/member?offset=250');
});

test('creates pagination data without next page', function () {
    $paginationData = new PaginationData(
        count: 150
    );

    expect($paginationData->count)->toBe(150)
        ->and($paginationData->next)->toBeNull();
});

test('hasNextPage returns true when next page exists', function () {
    $paginationData = new PaginationData(
        count: 250,
        next: 'https://api.congress.gov/v3/member?offset=250'
    );

    expect($paginationData->hasNextPage())->toBeTrue();
});

test('hasNextPage returns false when next page does not exist', function () {
    $paginationData = new PaginationData(count: 150);

    expect($paginationData->hasNextPage())->toBeFalse();
});

test('pagination data is readonly', function () {
    $paginationData = new PaginationData(count: 250);

    expect(fn () => $paginationData->count = 100)
        ->toThrow(Error::class);
});
