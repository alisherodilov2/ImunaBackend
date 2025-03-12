<?php

namespace Database\Seeders;

use App\Models\Brend;
use Illuminate\Database\Seeder;

class BrendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Brend::factory(3)->create();
    }
}
