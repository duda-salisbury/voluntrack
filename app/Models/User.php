<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Organization;
use App\Models\VolunteerEntry;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_platform_admin'])]
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
            'is_platform_admin' => 'boolean',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withPivot('role')->withTimestamps();
    }

    public function volunteerEntries(): HasMany
    {
        return $this->hasMany(VolunteerEntry::class, 'volunteer_user_id')->where('volunteer_entries.is_group_total', false);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function hasOrganizationRole(int $organizationId, ?string $role = null): bool
    {
        $query = $this->organizations()->where('organizations.id', $organizationId);
        if ($role !== null) {
            $query->wherePivot('role', $role);
        }

        return $query->exists();
    }

    public function isPlatformWorkspace(): bool
    {
        return $this->isSuperAdmin() && ! session()->has('admin_organization_id');
    }

    public function adminOrganizationId(): ?int
    {
        return session()->has('admin_organization_id') ? (int) session('admin_organization_id') : null;
    }

    public function adminOrganization(): ?Organization
    {
        $id = $this->adminOrganizationId();

        if ($id === null) {
            return null;
        }
        if ($this->isSuperAdmin()) {
            return Organization::find($id);
        }

        return $this->organizations()
            ->where('organizations.id', $id)->where('organizations.is_active', true)
            ->wherePivot('role', 'admin')->first();
    }
}
