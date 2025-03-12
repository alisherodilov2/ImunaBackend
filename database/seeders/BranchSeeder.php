<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $branch = [
            [
                'id' => 1,
                'name' => 'Oxunboboyev',
            ],
            [
                'id' => 2,
                'name' => 'Sardoba',
            ],
            [
                'id' => 3,
                'name' => 'Chorsu',
            ],
        ];
        Branch::insert($branch);
    }
}
