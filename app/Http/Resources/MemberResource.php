<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Member
 */
class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bioguide_id' => $this->bioguide_id,
            'name' => $this->name,
            'party_name' => $this->party_name,
            'state' => $this->state,
            'district' => $this->district,
            'url' => $this->url,
            'depiction_attribution' => $this->depiction_attribution,
            'depiction_image_url' => $this->depiction_image_url,
            'updated_date' => $this->updated_date?->format('Y-m-d H:i:s'),
            'updated_date_human' => $this->updated_date?->diffForHumans(),
            'terms' => MemberTermResource::collection($this->whenLoaded('terms')),
            'terms_count' => $this->when(
                $this->relationLoaded('terms'),
                fn () => $this->terms->count()
            ),
            'current_chamber' => $this->when(
                $this->relationLoaded('terms'),
                fn () => $this->terms
                    ->where('end_year', null)
                    ->first()
                    ?->chamber
            ),
        ];
    }
}
