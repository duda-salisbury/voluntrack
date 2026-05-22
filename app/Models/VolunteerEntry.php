<?php

namespace App\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VolunteerEntry extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'volunteer_user_id',
        'anon_token',
        'name',
        'location_id',
        'volunteered_at',
        'hours',
        'source',
        'is_matched',
        'matched_by_admin_id',
        'notes',
    ];

    protected $casts = [
        'volunteered_at' => 'date',
        'hours' => 'decimal:2',
        'is_matched' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The registered volunteer account that submitted this entry, if any.
     */
    public function volunteerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'volunteer_user_id');
    }

    /**
     * Get the location for this volunteer entry.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the admin who matched this entry.
     */
    public function matchedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by_admin_id');
    }

    /**
     * Scope to get only matched entries.
     */
    public function scopeMatched(Builder $query): Builder
    {
        return $query->where('is_matched', true);
    }

    /**
     * Scope to get only unmatched entries.
     */
    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->where('is_matched', false);
    }

    /**
     * Scope to get entries submitted by a specific volunteer account.
     */
    public function scopeByVolunteerUser(Builder $query, int $userId): Builder
    {
        return $query->where('volunteer_user_id', $userId);
    }

    /**
     * Scope to get entries by source.
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }
}
