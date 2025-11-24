<?php

declare(strict_types=1);

namespace App\DTO;

use Spatie\LaravelData\Data;

class MemberListFiltersData extends Data
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $party = null,
        public readonly ?string $state = null,
        public readonly string $sortBy = 'updated_date',
        public readonly string $sortDirection = 'desc',
        public readonly int $perPage = 25,
    ) {}

    public function hasFilters(): bool
    {
        return $this->name !== null
            || $this->party !== null
            || $this->state !== null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'party' => $this->party,
            'state' => $this->state,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
        ];
    }
}
