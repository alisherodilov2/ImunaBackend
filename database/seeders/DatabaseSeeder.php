<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call([
            UserSeeder::class,
            // ClientSeeder::class,
            // BranchSeeder::class,
            // UnitySeeder::class,
            // CategorySeeder::class,
            // ProductSeeder::class,
            // CustomerSeeder::class,
            // CurrencySeeder::class,
        ]);
    }
}
