<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecialEvent extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'description', 'starts_on', 'ends_on', 'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function volunteerEntries(): HasMany
    {
        return $this->hasMany(VolunteerEntry::class);
    }

    public function scheduleStatus(): string
    {
        if ($this->starts_on->isAfter(today())) {
            return 'Upcoming';
        }

        return ($this->ends_on ?? $this->starts_on)->isBefore(today()) ? 'Completed' : 'Current';
    }
}
