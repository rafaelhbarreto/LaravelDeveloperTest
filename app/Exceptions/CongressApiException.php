<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CongressApiException extends Exception
{
    /**
     * Create a new CongressApiException for a missing API key.
     *
     * @return self
     */
    public static function missingApiKey(): self
    {
        return new self('Congress API key is not configured. Set CONGRESS_API_KEY in .env');
    }

    /**
     * Create a new CongressApiException for a failed request.
     *
     * @param int $statusCode The status code of the response
     * @param string $url The URL of the request
     * @return self
     */
    public static function requestFailed(int $statusCode, string $url): self
    {
        return new self("Failed to fetch data from Congress API. Status: {$statusCode}, URL: {$url}");
    }
}
