<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get all volunteer entries for this location.
     */
    public function volunteerEntries(): HasMany
    {
        return $this->hasMany(VolunteerEntry::class);
    }
}
