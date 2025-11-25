<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CongressApiClientInterface;
use App\DTO\MemberData;
use App\DTO\PaginationData;
use App\Exceptions\CongressApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CongressApiClient implements CongressApiClientInterface
{
    private const RETRY_TIMES = 3;
    private const RETRY_DELAY_MS = 100;

    private readonly string $baseUrl;
    private readonly string $apiKey;
    private readonly int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('congress.api.base_url');
        $this->apiKey = config('congress.api.api_key');
        $this->timeout = config('congress.api.timeout');

        $this->ensureApiKeyIsConfigured();
    }

    public function fetchMembersPage(int $limit, int $offset = 0): array
    {
        $url = "{$this->baseUrl}/member";

        try {
            $response = $this->makeRequest()->get($url, [
                'api_key' => $this->apiKey,
                'offset' => $offset,
                'limit' => $limit,
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->logFailedRequest($e->response, $url);
            throw CongressApiException::requestFailed($e->response->status(), $url);
        }

        $this->validateResponse($response, $url);

        return $this->parseResponse($response->json());
    }

    public function fetchMembersFromUrl(string $url): array
    {
        try {
            $response = $this->makeRequest()->get($url);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->logFailedRequest($e->response, $url);
            throw CongressApiException::requestFailed($e->response->status(), $url);
        }

        $this->validateResponse($response, $url);

        return $this->parseResponse($response->json());
    }

    private function ensureApiKeyIsConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw CongressApiException::missingApiKey();
        }
    }

    private function validateResponse(Response $response, string $url): void
    {
        if ($response->successful()) {
            return;
        }

        $this->logFailedRequest($response, $url);

        throw CongressApiException::requestFailed($response->status(), $url);
    }

    private function logFailedRequest(Response $response, string $url): void
    {
        Log::error('Congress API request failed', [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }

    private function parseResponse(array $responseData): array
    {
        return [
            'members' => $this->parseMembers($responseData),
            'pagination' => $this->parsePagination($responseData),
        ];
    }

    private function parseMembers(array $responseData): array
    {
        $membersRaw = $responseData['members'] ?? [];

        if (!is_array($membersRaw)) {
            return [];
        }

        $members = [];

        foreach ($membersRaw as $memberRaw) {
            $member = $this->parseSingleMember($memberRaw);

            if ($member !== null) {
                $members[] = $member;
            }
        }

        return $members;
    }

    private function parseSingleMember(array $memberRaw): ?MemberData
    {
        try {
            return MemberData::fromApiResponse($memberRaw);
        } catch (\Throwable $e) {
            $this->logMemberParsingFailure($memberRaw, $e);
            return null;
        }
    }

    private function logMemberParsingFailure(array $memberRaw, \Throwable $e): void
    {
        Log::warning('Failed to parse member data', [
            'bioguideId' => $memberRaw['bioguideId'] ?? 'unknown',
            'error' => $e->getMessage(),
        ]);
    }

    private function parsePagination(array $responseData): PaginationData
    {
        return new PaginationData(
            count: $responseData['pagination']['count'] ?? 0,
            next: $responseData['pagination']['next'] ?? null,
        );
    }

    private function makeRequest(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->withHeaders(['Accept' => 'application/json'])
            ->retry(self::RETRY_TIMES, self::RETRY_DELAY_MS);
    }
}
