<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EPin; // Import the model you want to seed

class EPinSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seed the database with EPin model instances
        \App\Models\EPin::factory(10)->create();
    }
}
