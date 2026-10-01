<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (config('marketplace.demo') && ! app()->isProduction()) {
            $this->call(MarketplaceSeeder::class);
        }
    }
}
