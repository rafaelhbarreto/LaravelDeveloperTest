<?php

declare(strict_types=1);

namespace App\DTO;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class MemberTermData extends Data
{
    public function __construct(
        public readonly string $chamber,
        #[MapInputName('startYear')]
        public readonly int $startYear,
        #[MapInputName('endYear')]
        public readonly ?int $endYear = null,
    ) {
    }
}
