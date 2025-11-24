<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\MemberData;
use App\DTO\PaginationData;

interface CongressApiClientInterface
{
    /**
     * Fetch a page of members from the Congress API.
     *
     * @param int $limit Number of records to fetch per request
     * @param int $offset Starting offset for pagination
     * @return array{members: array<int, MemberData>, pagination: PaginationData}
     */
    public function fetchMembersPage(int $limit, int $offset = 0): array;

    /**
     * Fetch members from a specific URL (used for pagination.next).
     *
     * @param string $url The full URL to fetch
     * @return array{members: array<int, MemberData>, pagination: PaginationData}
     */
    public function fetchMembersFromUrl(string $url): array;
}
