<?php

namespace Database\Seeders;

use App\Models\Unity;
use Illuminate\Database\Seeder;

class UnitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $unity = [
            [
                'id' => 1,
                'name' => 'kg',
            ],
            [
                'id' => 2,
                'name' => 't',
            ],
            [
                'id' => 3,
                'name' => 'dona',
            ],
        ];
        Unity::insert($unity);
    }
}
