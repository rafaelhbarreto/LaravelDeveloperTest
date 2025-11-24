<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class MemberData extends Data
{
    public function __construct(
        #[MapInputName('bioguideId')]
        public readonly string $bioguideId,
        public readonly string $name,
        #[MapInputName('partyName')]
        public readonly ?string $partyName,
        public readonly ?string $state,
        public readonly ?int $district,
        #[MapInputName('updateDate')]
        public readonly Carbon $updateDate,
        public readonly ?string $url,
        public readonly ?string $depictionAttribution,
        public readonly ?string $depictionImageUrl,
        /** @var DataCollection<int, MemberTermData> */
        #[DataCollectionOf(MemberTermData::class)]
        public readonly DataCollection $terms,
    ) {
    }

    public static function fromApiResponse(array $data): self
    {
        $termsRaw = $data['terms']['item'] ?? [];

        if (!is_array($termsRaw)) {
            $termsRaw = [];
        }

        $termsData = array_map(
            fn (array $term) => MemberTermData::from($term),
            $termsRaw
        );

        return new self(
            bioguideId: $data['bioguideId'],
            name: $data['name'],
            partyName: $data['partyName'] ?? null,
            state: $data['state'] ?? null,
            district: isset($data['district']) ? (int) $data['district'] : null,
            updateDate: Carbon::parse($data['updateDate']),
            url: $data['url'] ?? null,
            depictionAttribution: $data['depiction']['attribution'] ?? null,
            depictionImageUrl: $data['depiction']['imageUrl'] ?? null,
            terms: new DataCollection(MemberTermData::class, $termsData),
        );
    }
}
