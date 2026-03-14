<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (config('inventory.seed_demo')) {
            $this->call(DemoSeeder::class);
        } else {
            $this->command?->info('SEED_DEMO is off, nothing to seed.');
        }
    }
}
