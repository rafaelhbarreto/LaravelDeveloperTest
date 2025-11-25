<?php

declare(strict_types=1);

namespace App\DTO;

use Spatie\LaravelData\Data;

class PaginationData extends Data
{
    public function __construct(
        public readonly int $count,
        public readonly ?string $next = null,
    ) {
    }

    public function hasNextPage(): bool
    {
        return $this->next !== null;
    }
}
