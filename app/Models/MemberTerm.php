<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTerm extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'member_id',
        'chamber',
        'start_year',
        'end_year',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'member_id' => 'integer',
        'start_year' => 'integer',
        'end_year' => 'integer',
    ];

    /**
     * Get the member that owns the term.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
