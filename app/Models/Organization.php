<?php

namespace App\Models;

use App\Support\OrganizationSettings;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Organization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'accent_color',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    // -------------------------------------------------------------------------
    // Route model binding
    // -------------------------------------------------------------------------

    /** Public routes bind by slug: /{org:slug} */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // -------------------------------------------------------------------------
    // Computed attributes
    // -------------------------------------------------------------------------

    /**
     * Typed settings accessor.  Always returns a fully-populated value object
     * with safe defaults for any missing JSON keys.
     *
     * Usage in Blade: {{ $org->config->welcomeTitle }}
     */
    public function config(): Attribute
    {
        return Attribute::get(
            fn () => OrganizationSettings::fromArray($this->settings ?? [])
        );
    }

    /**
     * Absolute URL for the logo, or null if none has been uploaded.
     */
    public function logoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo_path ? Storage::url($this->logo_path) : null
        );
    }

    /**
     * Accent colour as an "R, G, B" string for Bootstrap's --bs-primary-rgb
     * CSS custom property.  Falls back to Bootstrap's default blue.
     */
    public function accentColorRgb(): Attribute
    {
        return Attribute::get(function () {
            $hex = ltrim($this->accent_color ?? '#0d6efd', '#');

            if (strlen($hex) !== 6) {
                return '13, 110, 253'; // Bootstrap default blue
            }

            return implode(', ', [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ]);
        });
    }

    public function accentTextColor(): Attribute
    {
        return Attribute::get(function () {
            $hex = ltrim($this->accent_color ?? '#0d6efd', '#');
            $channels = array_map(function ($offset) use ($hex) {
                $channel = hexdec(substr($hex, $offset, 2)) / 255;

                return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
            }, [0, 2, 4]);
            $luminance = $channels[0] * 0.2126 + $channels[1] * 0.7152 + $channels[2] * 0.0722;

            return 1.05 / ($luminance + 0.05) >= ($luminance + 0.05) / 0.05 ? '#ffffff' : '#000000';
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function volunteerEntries(): HasMany
    {
        return $this->hasMany(VolunteerEntry::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function specialEvents(): HasMany
    {
        return $this->hasMany(SpecialEvent::class);
    }
}
