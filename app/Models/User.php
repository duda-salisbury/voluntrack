<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Organization;
use App\Models\VolunteerEntry;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'organization_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'         => 'hashed',
            'is_admin'         => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function volunteerEntries(): HasMany
    {
        return $this->hasMany(VolunteerEntry::class, 'volunteer_user_id');
    }

    /**
     * Super-admins have no assigned organization and can see all data.
     * Org-admins have an organization_id and are scoped to that org only.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && $this->organization_id === null;
    }

    /**
     * Returns true for non-admin users (registered volunteer accounts).
     */
    public function isVolunteer(): bool
    {
        return ! $this->is_admin;
    }
}
