<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {        $locations = [
            [
                'name' => 'Natchaug State Forest',
                'description' => 'Trail maintenance and improvements at Natchaug State Forest trails',
            ],
            [
                'name' => 'Pachaug State Forest',
                'description' => 'Trail work in Pachaug State Forest system',
            ],
            [
                'name' => 'Goodwin State Forest',
                'description' => 'Trail building and maintenance at Goodwin State Forest',
            ],
            [
                'name' => 'James L. Goodwin State Forest',
                'description' => 'Trail projects at James L. Goodwin State Forest',
            ],
            [
                'name' => 'Other Location',
                'description' => 'Other trail maintenance locations in the Quiet Corner region',
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}
