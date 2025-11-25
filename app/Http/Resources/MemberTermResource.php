<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MemberTerm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MemberTerm
 */
class MemberTermResource extends JsonResource
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
            'chamber' => $this->chamber,
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'is_current' => $this->end_year === null,
            'duration' => $this->end_year
                ? ($this->end_year - $this->start_year).' years'
                : 'Current',
        ];
    }
}
