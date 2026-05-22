<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\Models\VolunteerEntry;
use App\Support\OrganizationSettings;
use Illuminate\Database\Seeder;

/**
 * Creates a default organization and back-fills existing rows.
 * Safe to run multiple times (uses firstOrCreate).
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $settings = OrganizationSettings::fromArray([
            'welcome_title'   => 'Log Your Volunteer Hours',
            'welcome_message' => 'Track your volunteer trail work and conservation efforts.',
        ]);

        $org = Organization::firstOrCreate(
            ['slug' => 'default'],
            [
                'name'         => 'Voluntrack',
                'accent_color' => '#0d6efd',
                'is_active'    => true,
                'settings'     => $settings->toArray(),
            ]
        );

        // Back-fill existing locations that have no org yet
        Location::whereNull('organization_id')->update(['organization_id' => $org->id]);

        // Back-fill existing volunteer entries that have no org yet
        VolunteerEntry::whereNull('organization_id')->update(['organization_id' => $org->id]);

        // Do NOT touch admin users here — super-admins have organization_id = null
        // intentionally, and org-admins are assigned explicitly when creating orgs.

        $this->command->info("Default organization \"{$org->name}\" (slug: {$org->slug}) ready.");
    }
}
