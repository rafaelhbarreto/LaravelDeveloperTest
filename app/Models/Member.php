<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bioguide_id',
        'depiction_attribution',
        'depiction_image_url',
        'name',
        'party_name',
        'state',
        'district',
        'updated_date',
        'url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'district' => 'integer',
        'updated_date' => 'datetime',
    ];

    /**
     * Get the terms for the member.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(MemberTerm::class);
    }
}
