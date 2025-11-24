<?php

declare(strict_types=1);

use App\DTO\MemberTermData;

test('creates member term data with all fields', function () {
    $termData = new MemberTermData(
        chamber: 'House of Representatives',
        startYear: 2020,
        endYear: 2022
    );

    expect($termData->chamber)->toBe('House of Representatives')
        ->and($termData->startYear)->toBe(2020)
        ->and($termData->endYear)->toBe(2022);
});

test('creates member term data with null end year', function () {
    $termData = new MemberTermData(
        chamber: 'Senate',
        startYear: 2023
    );

    expect($termData->chamber)->toBe('Senate')
        ->and($termData->startYear)->toBe(2023)
        ->and($termData->endYear)->toBeNull();
});

test('member term data is readonly', function () {
    $termData = new MemberTermData(
        chamber: 'House of Representatives',
        startYear: 2020,
        endYear: 2022
    );

    expect(fn () => $termData->chamber = 'Senate')
        ->toThrow(Error::class);
});
